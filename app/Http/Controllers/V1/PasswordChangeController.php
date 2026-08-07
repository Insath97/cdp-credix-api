<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Models\PasswordChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PasswordChangeController extends Controller
{

    /**
     * Resolve the recipient's phone number based on user type
     * (Customer.phone_primary for customers, Employee.phone_primary otherwise).
     */
    private function resolveRecipientPhone(\App\Models\User $user): ?string
    {
        if ($user->user_type === 'customer') {
            return $user->customer?->phone_primary;
        }

        return $user->employee?->phone_primary;
    }

    public function requestChange(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (! in_array($user->user_type, ['staff', 'customer'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This feature is only available for staff and customer users.',
                ], 403);
            }

            $user->load($user->user_type === 'customer' ? 'customer' : 'employee');
            $phone = $this->resolveRecipientPhone($user);

            if (empty($phone)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Primary phone number not found. Cannot send OTP.',
                ], 400);
            }

            // Check if there's already a pending or active approved request
            $existingRequest = PasswordChangeRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have a pending password change request.',
                ], 400);
            }

            if (is_null($user->password_changed_at)) {
                // First time changing password
                $otp = (string) rand(100000, 999999);
                $expiresAt = now()->addMinutes(60);

                $changeRequest = PasswordChangeRequest::create([
                    'user_id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'otp' => $otp,
                    'expires_at' => $expiresAt,
                    'status' => 'approved',
                ]);

                // Queue SMS
                $message = config('app.name') . ": Your OTP for password change is {$otp}. Valid for 60 minutes.";
                SendSmsJob::dispatch($phone, $message);
                Log::info('First-time password change OTP SMS queued', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP sent successfully to your primary phone number.',
                ], 200);

            } else {
                // Subsequent change: requires admin approval
                $changeRequest = PasswordChangeRequest::create([
                    'user_id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'status' => 'pending',
                ]);

                // Send SMS notification to Super Admin users
                $admins = \App\Models\User::role('Super Admin')->where('is_active', true)->with('employee')->get();
                $notifiedPhones = [];
                foreach ($admins as $admin) {
                    if ($admin->employee && !empty($admin->employee->phone_primary)) {
                        $adminMessage = config('app.name') . ": {$user->name} ({$user->username}) requested a password change approval.";
                        SendSmsJob::dispatch($admin->employee->phone_primary, $adminMessage);
                        $notifiedPhones[] = $admin->employee->phone_primary;
                    }
                }
                Log::info('Password change request: admin SMS notifications queued', [
                    'change_request_id' => $changeRequest->id,
                    'admin_count' => count($admins),
                    'notified_phones' => $notifiedPhones,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Password change request sent to admin for approval.',
                ], 200);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process request',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function changeWithOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|numeric|digits:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth('api')->user();

            $changeRequest = PasswordChangeRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('otp', $request->otp)
                ->latest()
                ->first();

            if (! $changeRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP or no approved request found.',
                ], 400);
            }

            if ($changeRequest->expires_at && $changeRequest->expires_at->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OTP has expired.',
                ], 400);
            }

            // Update user password
            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
            ]);

            // Mark request as verified
            $changeRequest->update(['status' => 'verified']);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully.',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to change password',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = \App\Models\User::where('username', $request->username)
                ->orWhere('email', $request->username)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            if (! in_array($user->user_type, ['staff', 'customer'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This feature is only available for staff and customer users.',
                ], 403);
            }

            $user->load($user->user_type === 'customer' ? 'customer' : 'employee');

            if (empty($this->resolveRecipientPhone($user))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Primary phone number not found. Cannot request password change.',
                ], 400);
            }

            // Check if there's already a pending or active approved request
            $existingRequest = PasswordChangeRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have a pending password change request.',
                ], 400);
            }

            // Forgot password requires admin approval
            $changeRequest = PasswordChangeRequest::create([
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'status' => 'pending',
            ]);

            // Send SMS notification to Super Admin users
            $admins = \App\Models\User::role('Super Admin')->where('is_active', true)->with('employee')->get();
            $notifiedPhones = [];
            foreach ($admins as $admin) {
                if ($admin->employee && !empty($admin->employee->phone_primary)) {
                    $adminMessage = config('app.name') . ": {$user->name} ({$user->username}) requested a password reset approval.";
                    SendSmsJob::dispatch($admin->employee->phone_primary, $adminMessage);
                    $notifiedPhones[] = $admin->employee->phone_primary;
                }
            }
            Log::info('Forgot password: admin SMS notifications queued', [
                'change_request_id' => $changeRequest->id,
                'admin_count' => count($admins),
                'notified_phones' => $notifiedPhones,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset request sent to admin for approval.',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process request',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function resetForgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'otp' => 'required|numeric|digits:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = \App\Models\User::where('username', $request->username)
                ->orWhere('email', $request->username)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            $changeRequest = PasswordChangeRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('otp', $request->otp)
                ->latest()
                ->first();

            if (!$changeRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP or no approved request found.',
                ], 400);
            }

            if ($changeRequest->expires_at && $changeRequest->expires_at->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OTP has expired.',
                ], 400);
            }

            // Update user password
            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
            ]);

            // Mark request as verified
            $changeRequest->update(['status' => 'verified']);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully.',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to change password',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
