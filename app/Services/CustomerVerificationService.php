<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerVerification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerVerificationService
{
    public const OTP_TTL_MINUTES = 30;
    public const MAX_ATTEMPTS = 5;

    public function __construct(
        protected CustomerVerificationJwtService $jwtService,
        protected SmsService $smsService
    ) {
    }

    /**
     * Build an immutable snapshot of customer KYC details.
     */
    public function buildCustomerSnapshot(Customer $customer): array
    {
        $customer->loadMissing(['customerDetail', 'bankDetails']);

        return [
            'captured_at' => now()->toIso8601String(),
            'customer' => [
                'id'                 => $customer->id,
                'customer_id'        => $customer->customer_id,
                'customer_code'      => $customer->customer_code,
                'full_name'          => $customer->full_name,
                'name_with_initials' => $customer->name_with_initials,
                'id_type'            => $customer->id_type,
                'id_number'          => $customer->id_number,
                'date_of_birth'      => $customer->date_of_birth?->format('Y-m-d'),
                'phone_primary'      => $customer->phone_primary,
                'phone_secondary'    => $customer->phone_secondary,
                'email'              => $customer->email,
                'have_whatsapp'      => (bool) $customer->have_whatsapp,
                'whatsapp_number'    => $customer->whatsapp_number,
                'preferred_language' => $customer->preferred_language,
                'address' => [
                    'line_1'      => $customer->address_line_1,
                    'line_2'      => $customer->address_line_2,
                    'landmark'    => $customer->landmark,
                    'city'        => $customer->city,
                    'state'       => $customer->state,
                    'country'     => $customer->country,
                    'postal_code' => $customer->postal_code,
                ],
                'employment' => [
                    'status'         => $customer->employment_status,
                    'occupation'     => $customer->occupation,
                    'employer_name'  => $customer->employer_name,
                    'employer_phone' => $customer->employer_phone,
                    'employer_email' => $customer->employer_email,
                    'monthly_income' => $customer->monthly_income,
                ],
                'business' => [
                    'name'         => $customer->business_name,
                    'reg_no'       => $customer->business_registration_number,
                    'nature'       => $customer->business_nature,
                    'phone'        => $customer->business_phone,
                    'email'        => $customer->business_email,
                ],
                'financials' => [
                    'fixed_allowances'       => $customer->fixed_allowances,
                    'other_allowances'       => $customer->other_allowances,
                    'other_income'           => $customer->other_income,
                    'total_monthly_income'   => $customer->total_monthly_income,
                    'other_expenses'         => $customer->other_expenses,
                    'total_monthly_expenses' => $customer->total_monthly_expenses,
                ],
                'region_details' => $customer->customerDetail ? [
                    'gn_division' => $customer->customerDetail->gn_division,
                    'ds_division' => $customer->customerDetail->ds_division,
                    'district'    => $customer->customerDetail->district,
                    'province'    => $customer->customerDetail->province,
                ] : null,
                'bank_details' => $customer->bankDetails->map(fn ($bank) => [
                    'bank_name'      => $bank->bank_name,
                    'branch_name'    => $bank->branch_name,
                    'account_number' => $bank->account_number,
                    'account_name'   => $bank->account_name,
                ])->toArray(),
            ],
        ];
    }

    /**
     * Step 1: Initiate KYC verification (generate snapshot, OTP, JWT and send SMS).
     */
    public function initiate(Customer $customer, ?User $staffUser = null): array
    {
        if (empty($customer->phone_primary)) {
            throw new Exception("Customer does not have a primary mobile number configured. Cannot send OTP.");
        }

        return DB::transaction(function () use ($customer, $staffUser) {
            // Supersede any pending verifications for this customer
            CustomerVerification::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->update(['status' => 'superseded']);

            $snapshot = $this->buildCustomerSnapshot($customer);
            $version = CustomerVerification::where('customer_id', $customer->id)->count() + 1;

            $otp = sprintf('%06d', random_int(100000, 999999));
            $otpHash = hash('sha256', $otp);
            $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

            $verification = CustomerVerification::create([
                'customer_id'      => $customer->id,
                'created_by'       => $staffUser?->id,
                'phone_number'     => $customer->phone_primary,
                'snapshot_version' => $version,
                'snapshot_data'    => $snapshot,
                'otp_hash'         => $otpHash,
                'status'           => 'pending',
                'attempts'         => 0,
                'expires_at'       => $expiresAt,
            ]);

            $token = $this->jwtService->generateToken(
                $verification->id,
                $customer->id,
                $version,
                $otpHash,
                self::OTP_TTL_MINUTES
            );

            // Send SMS via SmsService
            $smsMessage = "CDP Capital: Your OTP to verify your customer details is {$otp}. Valid for " . self::OTP_TTL_MINUTES . " minutes.";
            $smsSent = false;
            try {
                $smsSent = $this->smsService->sendSms($customer->phone_primary, $smsMessage);
            } catch (\Throwable $e) {
                Log::error("Failed to send Customer KYC OTP SMS: " . $e->getMessage(), [
                    'customer_id' => $customer->id,
                    'phone'       => $customer->phone_primary,
                ]);
            }

            Log::info("Customer KYC verification initiated", [
                'verification_id' => $verification->id,
                'customer_id'     => $customer->id,
                'phone'           => $customer->phone_primary,
                'sms_sent'        => $smsSent,
                'otp_dev'         => config('app.debug') ? $otp : '***',
            ]);

            return [
                'review_id'             => $verification->id,
                'snapshot_version'      => $version,
                'verification_token'    => $token,
                'customer_phone_masked' => Str::mask($customer->phone_primary, '*', 3, -2),
                'expires_at'            => $expiresAt->toIso8601String(),
                'sms_sent'              => $smsSent,
                'dev_otp'               => config('app.debug') ? $otp : null,
            ];
        });
    }

    /**
     * Verify the entered OTP against the signed JWT token.
     */
    public function verifyOtp(string $token, string $otp, ?User $staffUser = null): array
    {
        $claims = $this->jwtService->verifyToken($token);

        $verificationId = (int) $claims['review_id'];
        $customerId = (int) $claims['customer_id'];

        return DB::transaction(function () use ($verificationId, $customerId, $claims, $otp, $staffUser) {
            /** @var CustomerVerification $verification */
            $verification = CustomerVerification::lockForUpdate()->find($verificationId);

            if (!$verification) {
                throw new Exception("Verification record not found.");
            }

            if ($verification->customer_id !== $customerId) {
                throw new Exception("Verification record customer mismatch.");
            }

            if ($verification->status !== 'pending') {
                throw new Exception("This verification request is already {$verification->status}.");
            }

            if ($verification->isExpired()) {
                $verification->update(['status' => 'expired']);
                throw new Exception("Verification OTP has expired. Please request a new OTP.");
            }

            if ($verification->attempts >= self::MAX_ATTEMPTS) {
                $verification->update(['status' => 'failed']);
                throw new Exception("Maximum verification attempts exceeded. Please initiate a new verification.");
            }

            $enteredOtpHash = hash('sha256', trim($otp));

            if (!hash_equals($claims['otp_hash'], $enteredOtpHash) || !hash_equals($verification->otp_hash, $enteredOtpHash)) {
                $verification->increment('attempts');
                $remaining = self::MAX_ATTEMPTS - $verification->attempts;
                throw new Exception("Invalid OTP. {$remaining} attempts remaining.");
            }

            // Successfully verified!
            $verification->update([
                'status'      => 'verified',
                'verified_at' => now(),
                'verified_by' => $staffUser?->id,
            ]);

            // Mark customer as KYC verified
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->update([
                    'is_kyc_verified'             => true,
                    'kyc_verified_at'             => now(),
                    'current_kyc_verification_id' => $verification->id,
                ]);
            }

            Log::info("Customer KYC verified successfully", [
                'verification_id' => $verification->id,
                'customer_id'     => $customerId,
                'verified_by'     => $staffUser?->id,
            ]);

            return [
                'review_id'    => $verification->id,
                'customer_id'  => $customerId,
                'status'       => 'verified',
                'verified_at'  => $verification->verified_at->toIso8601String(),
                'customer'     => $customer?->only(['id', 'customer_id', 'full_name', 'id_number', 'is_kyc_verified', 'kyc_verified_at']),
            ];
        });
    }

    /**
     * Resend a fresh OTP for an existing pending verification.
     */
    public function resendOtp(string $token, ?User $staffUser = null): array
    {
        $claims = $this->jwtService->verifyToken($token);

        $verificationId = (int) $claims['review_id'];
        $customerId = (int) $claims['customer_id'];

        return DB::transaction(function () use ($verificationId, $customerId, $claims, $staffUser) {
            /** @var CustomerVerification $verification */
            $verification = CustomerVerification::lockForUpdate()->find($verificationId);

            if (!$verification) {
                throw new Exception("Verification record not found.");
            }

            if ($verification->status !== 'pending' && $verification->status !== 'expired') {
                throw new Exception("Cannot resend OTP for a verification that is {$verification->status}.");
            }

            $customer = Customer::find($customerId);
            if (!$customer || empty($customer->phone_primary)) {
                throw new Exception("Customer or customer primary phone not found.");
            }

            $otp = sprintf('%06d', random_int(100000, 999999));
            $otpHash = hash('sha256', $otp);
            $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

            $verification->update([
                'otp_hash'   => $otpHash,
                'status'     => 'pending',
                'attempts'   => 0,
                'expires_at' => $expiresAt,
            ]);

            $newToken = $this->jwtService->generateToken(
                $verification->id,
                $customer->id,
                $verification->snapshot_version,
                $otpHash,
                self::OTP_TTL_MINUTES
            );

            $smsMessage = "CDP Capital: Your new OTP to verify your customer details is {$otp}. Valid for " . self::OTP_TTL_MINUTES . " minutes.";
            $smsSent = false;
            try {
                $smsSent = $this->smsService->sendSms($customer->phone_primary, $smsMessage);
            } catch (\Throwable $e) {
                Log::error("Failed to resend Customer KYC OTP SMS: " . $e->getMessage(), [
                    'customer_id' => $customer->id,
                ]);
            }

            return [
                'review_id'             => $verification->id,
                'snapshot_version'      => $verification->snapshot_version,
                'verification_token'    => $newToken,
                'customer_phone_masked' => Str::mask($customer->phone_primary, '*', 3, -2),
                'expires_at'            => $expiresAt->toIso8601String(),
                'sms_sent'              => $smsSent,
                'dev_otp'               => config('app.debug') ? $otp : null,
            ];
        });
    }
}
