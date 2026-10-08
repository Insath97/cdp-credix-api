<?php

use App\Models\Customer;
use App\Models\CustomerVerification;
use App\Models\User;
use App\Services\CustomerLoanEligibilityService;
use App\Services\CustomerVerificationJwtService;
use App\Services\CustomerVerificationService;

test('CustomerVerificationJwtService generates and verifies token correctly', function () {
    $jwtService = app(CustomerVerificationJwtService::class);
    $reviewId = 42;
    $customerId = 101;
    $version = 1;
    $otp = '654321';
    $otpHash = hash('sha256', $otp);

    $token = $jwtService->generateToken($reviewId, $customerId, $version, $otpHash, 30);

    expect($token)->toBeString();
    expect(explode('.', $token))->toHaveCount(3);

    $claims = $jwtService->verifyToken($token);

    expect($claims['review_id'])->toBe($reviewId);
    expect($claims['customer_id'])->toBe($customerId);
    expect($claims['snapshot_version'])->toBe($version);
    expect($claims['otp_hash'])->toBe($otpHash);
    expect($claims['purpose'])->toBe('customer_kyc_verification');
});

test('CustomerVerificationService initiates verification and creates snapshot', function () {
    $customer = Customer::create([
        'full_name'     => 'Jane Doe',
        'id_number'     => '199012345678',
        'phone_primary' => '0771234567',
    ]);

    $service = app(CustomerVerificationService::class);
    $result = $service->initiate($customer);

    expect($result)->toHaveKeys([
        'review_id',
        'snapshot_version',
        'verification_token',
        'customer_phone_masked',
        'expires_at',
    ]);

    $verification = CustomerVerification::find($result['review_id']);
    expect($verification)->not->toBeNull();
    expect($verification->status)->toBe('pending');
    expect($verification->customer_id)->toBe($customer->id);
    expect($verification->snapshot_data)->toHaveKey('customer');
    expect($verification->snapshot_data['customer']['full_name'])->toBe('Jane Doe');
});

test('CustomerVerificationService verifies valid OTP and updates customer status', function () {
    $customer = Customer::create([
        'full_name'     => 'John Smith',
        'id_number'     => '198512345679',
        'phone_primary' => '0779876543',
    ]);

    expect($customer->is_kyc_verified)->toBeFalse();

    $service = app(CustomerVerificationService::class);
    $initiated = $service->initiate($customer);

    $token = $initiated['verification_token'];
    $otp = $initiated['dev_otp'];

    expect($otp)->toBeString();

    // Verifying with wrong OTP fails
    expect(fn () => $service->verifyOtp($token, '000000'))
        ->toThrow(\Exception::class, 'Invalid OTP');

    // Verifying with correct OTP succeeds
    $verified = $service->verifyOtp($token, $otp);

    expect($verified['status'])->toBe('verified');

    $customer->refresh();
    expect($customer->is_kyc_verified)->toBeTrue();
    expect($customer->kyc_verified_at)->not->toBeNull();
    expect($customer->current_kyc_verification_id)->toBe($initiated['review_id']);
});

test('CustomerLoanEligibilityService refuses loan for unverified customer and allows verified customer', function () {
    $customer = Customer::create([
        'full_name'       => 'Test Borrower',
        'id_number'       => '199212345670',
        'phone_primary'   => '0775551234',
        'is_kyc_verified' => false,
    ]);

    $refusal = CustomerLoanEligibilityService::refusalForUnverifiedKyc($customer->id);
    expect($refusal)->not->toBeNull();
    expect($refusal)->toContain('KYC details must be confirmed and verified with OTP before applying for a loan');

    // Now verify customer
    $customer->update([
        'is_kyc_verified' => true,
        'kyc_verified_at' => now(),
    ]);

    $refusalAfter = CustomerLoanEligibilityService::refusalForUnverifiedKyc($customer->id);
    expect($refusalAfter)->toBeNull();
});

test('HTTP API: customer verification flow endpoints work as expected', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);
    foreach (['Customer Update', 'Customer Create', 'Customer Index'] as $perm) {
        $p = Spatie\Permission\Models\Permission::findOrCreate($perm, 'api');
        $user->givePermissionTo($p);
    }

    $customer = Customer::create([
        'full_name'     => 'Alice Smith',
        'id_number'     => '199512345678',
        'phone_primary' => '0712345678',
    ]);

    $token = auth('api')->login($user);
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Initiate verification via API
    $response = $this->withHeaders($headers)
        ->postJson("/api/v1/customers/{$customer->id}/verification/initiate");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'review_id',
                'snapshot_version',
                'verification_token',
                'customer_phone_masked',
                'expires_at',
            ],
        ]);

    $verificationToken = $response->json('data.verification_token');
    $devOtp = $response->json('data.dev_otp');

    // 2. Check status endpoint
    $statusResponse = $this->withHeaders($headers)
        ->getJson("/api/v1/customers/{$customer->id}/verification/status");

    $statusResponse->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data'   => [
                'customer_id'     => $customer->id,
                'is_kyc_verified' => false,
            ],
        ]);

    // 3. Verify OTP via API
    $verifyResponse = $this->withHeaders($headers)
        ->postJson("/api/v1/customers/{$customer->id}/verification/verify-otp", [
            'verification_token' => $verificationToken,
            'otp'                => $devOtp,
        ]);

    $verifyResponse->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data'   => [
                'status' => 'verified',
            ],
        ]);

    // Customer is now KYC verified
    $customer->refresh();
    expect($customer->is_kyc_verified)->toBeTrue();

    // 4. Check status again
    $statusResponseAfter = $this->withHeaders($headers)
        ->getJson("/api/v1/customers/{$customer->id}/verification/status");

    $statusResponseAfter->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data'   => [
                'customer_id'     => $customer->id,
                'is_kyc_verified' => true,
            ],
        ]);
});

