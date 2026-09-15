<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Models\PasswordChangeRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PasswordChangeController extends Controller
{

    /**
     * Change the signed-in user's own password, proved by their current one.
     *
     * The OTP routes below exist for the case where the user cannot prove who
     * they are -- a forgotten password, or a first login. Someone already
     * signed in and able to type their existing password has proved it, and
     * sending them an SMS to re-prove it only fails whenever the phone on file
     * is stale, which on a staff record it often is.
     *
     * Every other session is left alone deliberately: invalidating them is a
     * separate decision, and doing it silently would sign the user out of the
     * device they are standing at.
     */
    public function changeWithCurrentPassword(Request $request)
    {
        try {
            $user = auth('api')->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password'         => 'required|string|min:8|confirmed|different:current_password',
            ], [
                'password.confirmed' => 'The new password and its confirmation do not match.',
                'password.different' => 'The new password must be different from the current one.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                // Named separately from the validation failures above: "that is
                // not your password" is the one thing the user can actually act
                // on, and burying it in a field error list hides it.
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Your current password is incorrect.',
                    'errors'  => ['current_password' => ['Your current password is incorrect.']],
                ], 422);
            }

            $user->update([
                'password'            => Hash::make($request->password),
                'password_changed_at' => now(),
            ]);

            Log::info('Password changed with current password', ['user_id' => $user->id]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Password changed successfully',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Password change failed', ['error' => $th->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to change password',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

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

    /**
     * Generate an OTP, store it as an already-approved PasswordChangeRequest,
     * and queue it via SMS.
     */
    private function issueDirectOtp(\App\Models\User $user, string $phone, string $purpose): \Illuminate\Http\JsonResponse
    {
        $otp = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(60);

        PasswordChangeRequest::create([
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'status' => 'approved',
        ]);

        $message = config('app.name') . ": Your OTP for {$purpose} is {$otp}. Valid for 60 minutes.";
        SendSmsJob::dispatch($phone, $message);
        Log::info("{$purpose} OTP SMS queued", [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent successfully to your primary phone number.',
        ], 200);
    }

    public function requestChange(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (! in_array($user->user_type, ['admin', 'staff', 'customer'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This feature is only available for admin, staff, and customer users.',
                ], 403);
            }

            $user->load($user->user_type === 'customer' ? 'customer:'.Customer::SUMMARY_COLUMNS : 'employee');
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

            return $this->issueDirectOtp($user, $phone, 'password change');

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

            if (! in_array($user->user_type, ['admin', 'staff', 'customer'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This feature is only available for admin, staff, and customer users.',
                ], 403);
            }

            $user->load($user->user_type === 'customer' ? 'customer:'.Customer::SUMMARY_COLUMNS : 'employee');
            $phone = $this->resolveRecipientPhone($user);

            if (empty($phone)) {
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

            return $this->issueDirectOtp($user, $phone, 'password reset');

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
