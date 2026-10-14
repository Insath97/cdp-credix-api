<?php

use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Models\Application;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\User;
use App\Services\CustomerLoanEligibilityService;
use App\Services\LoanApplicationWorkflowService;
use App\Services\LoanCustomerConfirmationService;
use App\Services\SmsService;

function createTestBorrower(bool $kycVerified = true): Customer
{
    static $i = 0;
    $i++;
    return Customer::create([
        'customer_id'     => 'CUS00' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
        'full_name'       => 'Test Borrower ' . $i,
        'id_type'         => 'nic',
        'id_number'       => '199000000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
        'phone_primary'   => '0771234' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
        'is_kyc_verified' => $kycVerified,
        'kyc_verified_at' => $kycVerified ? now() : null,
    ]);
}

function createSubmittedLoan(Customer $borrower): LoanApplication
{
    static $seq = 0;
    $seq++;

    $term = LoanTerm::firstOrCreate(
        ['code' => 'TERM_GEN'],
        ['title' => 'General Term']
    );

    $type = LoanType::firstOrCreate(
        ['code' => 'TYPE_STD'],
        ['title' => 'Standard', 'loan_term_id' => $term->id]
    );

    $product = LoanProduct::firstOrCreate(
        ['code' => 'PROD_TEST'],
        [
            'name'                 => 'Standard Micro Loan',
            'loan_type_id'         => $type->id,
            'loan_term_id'         => $term->id,
            'interest_rate'        => 12.0,
            'min_amount'           => 10000,
            'max_amount'           => 1000000,
            'min_term_months'      => 3,
            'max_term_months'      => 36,
            'processing_fee_type'  => 'percentage',
            'processing_fee_value' => 1.5,
            'is_active'            => true,
            'is_group_loan'        => false,
        ]
    );

    $app = Application::create([
        'application_no'   => 'APP-OTP-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        'application_type' => 'loan_application',
        'requested_amount' => 100000,
        'status'           => 'pending',
    ]);

    $creator = User::factory()->create();

    return LoanApplication::create([
        'application_id'          => $app->id,
        'customer_id'             => $borrower->id,
        'loan_product_id'         => $product->id,
        'loan_type_id'            => $type->id,
        'loan_term_id'            => $term->id,
        'requested_amount'        => 100000,
        'term_months'             => 12,
        'interest_rate'           => 12.0,
        'interest_type'           => 'flat',
        'monthly_installment'     => 9333.33,
        'processing_fee'          => 1500,
        'net_disbursement_amount' => 98500,
        'monthly_repayment_date'  => 5,
        'status'                  => LoanApplicationStatus::Submitted,
        'is_active'               => true,
        'applied_by'              => $creator->id,
        'applied_at'              => now(),
    ]);
}

test('Staff can review a submitted loan application without being blocked by OTP 2', function () {
    $borrower = createTestBorrower(kycVerified: true);
    $loan = createSubmittedLoan($borrower);

    $reviewer = User::factory()->create();
    $workflow = app(LoanApplicationWorkflowService::class);

    // The review transition must succeed without customer confirmation OTP (OTP 2)
    $reviewedLoan = $workflow->transition(
        $loan,
        LoanApplicationStatus::Reviewed,
        $reviewer->id,
        'Reviewing documents and eligibility'
    );

    expect($reviewedLoan->status)->toBe(LoanApplicationStatus::Reviewed);
});

test('Customer review link cannot be sent while loan application is still in Submitted stage', function () {
    $borrower = createTestBorrower(kycVerified: true);
    $loan = createSubmittedLoan($borrower);

    $confirmationService = app(LoanCustomerConfirmationService::class);

    expect(fn () => $confirmationService->sendLinks($loan))
        ->toThrow(\Exception::class, "Disbursement review links can only be issued once the loan offer has been accepted");
});

test('Customer KYC verification (OTP 1) remains strictly enforced before borrowing', function () {
    $unverifiedBorrower = createTestBorrower(kycVerified: false);

    $refusal = CustomerLoanEligibilityService::refusalForUnverifiedKyc($unverifiedBorrower->id);
    expect($refusal)->not->toBeNull();
    expect($refusal)->toContain('KYC details must be confirmed and verified with OTP');
});

test('Final loan-detail confirmation (OTP 2) is enforced at Disbursement stage', function () {
    $borrower = createTestBorrower(kycVerified: true);
    $loan = createSubmittedLoan($borrower);

    // Set to Accepted (ready for Disbursement) without OTP 2 confirmed
    $loan->update(['status' => LoanApplicationStatus::Accepted]);

    $disburser = User::factory()->create();
    $workflow = app(LoanApplicationWorkflowService::class);

    expect(fn () => $workflow->transition(
        $loan,
        LoanApplicationStatus::Disbursed,
        $disburser->id,
        'Disbursing loan funds'
    ))->toThrow(
        InvalidLoanApplicationTransitionException::class,
        "The customer must confirm the loan details with OTP before this loan application can be disbursed"
    );
});
