<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateCustomerVerificationRequest;
use App\Http\Requests\ResendCustomerOtpRequest;
use App\Http\Requests\VerifyCustomerOtpRequest;
use App\Models\Customer;
use App\Services\CustomerVerificationService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class CustomerVerificationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected CustomerVerificationService $verificationService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Customer Update|Customer Create|Customer Index', only: ['initiate', 'initiateWithCustomer', 'verifyOtp', 'resendOtp', 'status']),
        ];
    }

    /**
     * Step 1: Initiate KYC verification for a customer.
     * Generates immutable snapshot, creates OTP, sends SMS, and issues signed JWT.
     */
    public function initiate(InitiateCustomerVerificationRequest $request, ?string $customerId = null): JsonResponse
    {
        try {
            $id = $customerId ?: $request->input('customer_id');
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customer not found.',
                ], 404);
            }

            $result = $this->verificationService->initiate($customer, Auth::user());

            $this->logActivity('CREATE', 'CustomerVerification', "Customer KYC verification initiated for Customer #{$customer->id}", [
                'customer_id'     => $customer->id,
                'review_id'       => $result['review_id'],
                'snapshot_version'=> $result['snapshot_version'],
                'phone_masked'    => $result['customer_phone_masked'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'OTP sent to customer mobile successfully. Please confirm with OTP to verify customer details.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Convenience endpoint for initiating by customer_id in request body.
     */
    public function initiateWithCustomer(InitiateCustomerVerificationRequest $request): JsonResponse
    {
        return $this->initiate($request, $request->input('customer_id'));
    }

    /**
     * Step 2: Verify the entered OTP with the JWT token.
     */
    public function verifyOtp(VerifyCustomerOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->verificationService->verifyOtp(
                $request->input('verification_token'),
                $request->input('otp'),
                Auth::user()
            );

            $this->logActivity('UPDATE', 'CustomerVerification', "Customer #{$result['customer_id']} KYC verified with OTP", [
                'customer_id' => $result['customer_id'],
                'review_id'   => $result['review_id'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Customer details confirmed and verified successfully.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Resend OTP for a pending verification.
     */
    public function resendOtp(ResendCustomerOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->verificationService->resendOtp(
                $request->input('verification_token'),
                Auth::user()
            );

            $this->logActivity('UPDATE', 'CustomerVerification', "Customer KYC verification OTP resent for review #{$result['review_id']}", [
                'review_id'    => $result['review_id'],
                'phone_masked' => $result['customer_phone_masked'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'A new OTP has been sent to the customer mobile.',
                'data'    => $result,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Check the customer's current KYC verification status.
     */
    public function status(string $customerId): JsonResponse
    {
        try {
            $customer = Customer::with('currentKycVerification')->find($customerId);

            if (!$customer) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customer not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'customer_id'                 => $customer->id,
                    'customer_code'               => $customer->customer_code,
                    'full_name'                   => $customer->full_name,
                    'phone_primary'               => $customer->phone_primary,
                    'is_kyc_verified'             => (bool) $customer->is_kyc_verified,
                    'kyc_verified_at'             => $customer->kyc_verified_at?->toIso8601String(),
                    'current_kyc_verification_id' => $customer->current_kyc_verification_id,
                    'verification'                => $customer->currentKycVerification ? [
                        'id'               => $customer->currentKycVerification->id,
                        'snapshot_version' => $customer->currentKycVerification->snapshot_version,
                        'verified_at'      => $customer->currentKycVerification->verified_at?->toIso8601String(),
                        'verified_by'      => $customer->currentKycVerification->verified_by,
                    ] : null,
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
