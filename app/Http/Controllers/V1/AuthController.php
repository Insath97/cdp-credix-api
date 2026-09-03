<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\LoginOtpVerification;
use App\Models\User;
use App\Models\Customer;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Admin / Customer Login
     * Customers get an OTP-required response on their first login instead of a JWT.
     */
    public function login(Request $request)
    {
        try {
            if (!$request->has('login') && $request->has('email')) {
                $request->merge(['login' => $request->input('email')]);
            }

            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $loginVal = $request->input('login');
            $passwordVal = $request->input('password');

            // Find user by username or email
            $user = User::where('username', $loginVal)
                ->orWhere('email', $loginVal)
                ->first();

            // Fallback: search employee by id_number
            if (!$user) {
                $employee = \App\Models\Employee::where('id_number', $loginVal)->first();
                if ($employee) {
                    $user = User::where('employee_id', $employee->id)->first();
                }
            }

            if (!$user || !Hash::check($passwordVal, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('api');
            $token = $guard->login($user);
            $user = auth('api')->user();

            if (!$user->canLogin()) {
                Auth::guard('api')->logout();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is deactivated'
                ], 401);
            }

            if ($user->user_type !== 'customer' && !$user->roles()->exists()) {
                Auth::guard('api')->logout();
                return response()->json([
                    'success' => false,
                    'message' => 'No admin role assigned. Please contact Super Admin.'
                ], 403);
            }

            if ($user->user_type === 'customer' && is_null($user->two_factor_verified_at)) {
                $user->load('customer:' . Customer::SUMMARY_COLUMNS);
                $phone = $user->customer?->phone_primary;

                if (empty($phone)) {
                    Auth::guard('api')->logout();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Primary phone number not found. Cannot send OTP.'
                    ], 400);
                }

                // Invalidate the token minted above; it isn't handed out until OTP is verified
                Auth::guard('api')->logout();

                // Invalidate any prior unused OTP for this user
                LoginOtpVerification::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->delete();

                $otp = (string) rand(100000, 999999);
                $reference = (string) Str::uuid();

                LoginOtpVerification::create([
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(30),
                    'status' => 'approved',
                    'ip_address' => $request->ip(),
                ]);

                $message = config('app.name') . ": Your OTP for login is {$otp}. Valid for 30 minutes.";

                Log::info('OTP Login Debug', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'otp' => $otp,
                ]);

                try {
                    $smsService = app(SmsService::class);
                    $smsSent = $smsService->sendSms($phone, $message);
                    Log::info('OTP SMS send result', ['sent' => $smsSent, 'phone' => $phone]);
                } catch (\Throwable $smsEx) {
                    Log::error('OTP SMS send failed', ['error' => $smsEx->getMessage()]);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP sent successfully to your primary phone number.',
                    'data' => [
                        'otp_required' => true,
                        'reference' => $reference,
                        'expires_in' => 1800,
                    ]
                ], 200);
            }

            return $this->issueAuthenticatedResponse($user, $token, $request);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to login',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Verify first-login OTP and issue the JWT.
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'required|uuid',
                'otp' => 'required|numeric|digits:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $record = LoginOtpVerification::where('reference', $request->reference)
                ->where('status', 'approved')
                ->where('otp', $request->otp)
                ->latest()
                ->first();

            if (!$record) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP or no pending login verification found.'
                ], 400);
            }

            if ($record->expires_at->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'OTP has expired.'
                ], 400);
            }

            $user = User::find($record->user_id);

            if (!$user || !$user->canLogin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is deactivated'
                ], 401);
            }

            $record->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            if (is_null($user->two_factor_verified_at)) {
                $user->update(['two_factor_verified_at' => now()]);
            }

            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('api');
            $token = $guard->login($user);

            return $this->issueAuthenticatedResponse($user, $token, $request);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify OTP',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Build the standard login-success response (cookie + JWT + user w/ roles).
     */
    private function issueAuthenticatedResponse(User $user, string $token, Request $request)
    {
        $user->updateLastLogin($request->ip());

        $cookie = cookie(
            'auth_token',
            $token,
            60 * 24 * 7,
            '/',
            null,
            true,  // Secure
            true,  // HttpOnly
            false,
            'lax'
        );

        $user->load(['roles' => function ($query) {
            $query->select('id', 'name')
                ->with(['permissions' => function ($query) {
                    $query->select('id', 'name');
                }]);
        }]);

        if ($user->relationLoaded('roles')) {
            $user->roles->each->makeHidden(['pivot']);
            $user->roles->each(function ($role) {
                if ($role->relationLoaded('permissions')) {
                    $role->permissions->each->makeHidden(['pivot']);
                }
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'auth_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]
        ], 200)->cookie($cookie);
    }

    /**
     * Admin Logout
     */
    public function logout(Request $request)
    {
        try {
            // Logout the user (invalidates the token)
            Auth::guard('api')->logout();

            // Create an expired cookie to remove it from browser
            $cookie = Cookie::forget('auth_token');

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful'
            ], 200)->withCookie($cookie);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated admin user
     */
    public function me()
    {
        try {
            $user = auth('api')->user();

            $user->load(['roles' => function ($query) {
                $query->select('id', 'name')
                    ->with(['permissions' => function ($query) {
                        $query->select('id', 'name');
                    }]);
            }]);

            if ($user->relationLoaded('roles')) {
                $user->roles->each->makeHidden(['pivot']);
                $user->roles->each(function ($role) {
                    if ($role->relationLoaded('permissions')) {
                        $role->permissions->each->makeHidden(['pivot']);
                    }
                });
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User details fetched successfully',
                'data' => [
                    'user' => $user
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user details',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
