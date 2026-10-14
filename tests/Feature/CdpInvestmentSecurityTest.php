<?php

use App\Enums\LoanSecurityType;
use App\Exceptions\InvestmentCollateralException;
use App\Exceptions\SecurityLimitExceededException;
use App\Models\LoanApplicationSecurity;
use App\Models\Setting;
use App\Services\CdpConnectService;
use App\Services\InvestmentCollateralService;
use App\Services\LoanSecurityService;

/*
|--------------------------------------------------------------------------
| CDP Investment alongside other securities
|--------------------------------------------------------------------------
|
| These stub CDP Core, the only part of a CDP Investment security that leaves
| the process. What is pinned down here is rule 5: an investment pledged beside
| a property is counted toward the loan's aggregate coverage rather than held to
| carrying the whole loan on its own, while every rule about the policy itself
| stays in force.
|
*/

/** One approved investment, as CDP Core would report it. */
function coreInvestment(string $policy, float $amount, ?string $maturity = null): array
{
    return [
        'success'     => true,
        'status_code' => 200,
        'message'     => 'ok',
        'data'        => [
            'customer' => null,
            'investments' => [[
                'policy_number'     => $policy,
                'investment_amount' => $amount,
                'status'            => 'approved',
                'reservation_date'  => now()->subYear()->toDateString(),
                'maturity_date'     => $maturity ?? now()->addYears(5)->toDateString(),
                'product'           => ['name' => 'Endowment', 'code' => 'END', 'roi_percentage' => 9, 'duration_months' => 60],
            ]],
        ],
    ];
}

/**
 * Point CDP Core at the given payloads, keyed by the NIC they belong to.
 * Each borrower in these tests has its own NIC (customers.id_number is unique),
 * so the stub has to be registered against the NIC actually on the loan.
 */
function fakeCore(array $payloads): void
{
    app()->instance(CdpConnectService::class, new class($payloads) extends CdpConnectService
    {
        public function __construct(private array $payloads)
        {
            parent::__construct();
        }

        public function getCustomerInvestments(string $idNumber, ?string $idType = null, ?string $status = null): array
        {
            return $this->payloads[$idNumber]
                ?? ['success' => false, 'status_code' => 404, 'message' => 'none', 'data' => []];
        }
    });

    // The services hold the client they were built with, so they are rebuilt
    // rather than reusing the first one.
    app()->forgetInstance(LoanSecurityService::class);
    app()->forgetInstance(InvestmentCollateralService::class);
}

beforeEach(function () {
    // Plan 1 at what each type lent at before plans existed: CDP 50, Property 70.
    configurePlans(LoanSecurityType::CdpInvestment, ['plan_1' => 50]);
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70]);

    // Writing a security needs a signed-in officer holding the plan permission.
    $this->actingAs(officerWithPlans(), 'api');

    $this->customer = borrower();
    $this->product  = securedProduct();
    $this->nic      = $this->customer->id_number;

    fakeCore([$this->nic => coreInvestment('CDP-JFNM-1', 4_000_000)]);

    // A CDP Investment security for this test's borrower.
    $this->cdp = fn (string $policy = 'CDP-JFNM-1', ?string $nic = null, string $plan = 'plan_1') => [
        'security_type' => 'cdp_investment',
        'security_plan' => $plan,
        'investment_nic' => $nic ?? $this->nic,
        'policy_number' => $policy,
    ];
});

it('lends against an investment up to its own percentage when it secures the loan alone', function () {
    $loan = loanFor($this->product, $this->customer, 2_000_000);

    // 4,000,000 at 50% = 2,000,000, so 2,000,000 is all this lone policy carries.
    $rows = writeSecurities($loan, [($this->cdp)()], 2_000_000);

    expect((float) $rows[0]['pledged_value'])->toBe(4_000_000.0);
    expect((float) $rows[0]['max_loan_amount'])->toBe(2_000_000.0);
});

it('still refuses when one investment alone is asked to carry more than it can', function () {
    $loan = loanFor($this->product, $this->customer, 2_500_000);

    expect(fn () => writeSecurities($loan, [($this->cdp)()], 2_500_000))
        ->toThrow(InvestmentCollateralException::class);
});

it('lets an investment sit beside a property that on its own falls short', function () {
    $loan = loanFor($this->product, $this->customer, 8_000_000);

    // The property alone is worth 7,000,000 of it -- short of 8,000,000. The
    // investment is worth 2,000,000, so the pair covers the loan.
    $rows = writeSecurities($loan, [
        propertySecurity(['estimated_value' => 10_000_000]),
        ($this->cdp)(),
    ], 8_000_000);

    expect($rows)->toHaveCount(2);

    app(LoanSecurityService::class)->saveCollection($loan, $rows);

    $loan->refresh()->load('securities');

    expect($loan->securityCoverage())->toMatchArray([
        'security_count'                => 2,
        'total_pledged_value'           => 14_000_000.0,
        'total_eligible_security_value' => 9_000_000.0,
    ]);

    $investment = $loan->securities->firstWhere('security_type', LoanSecurityType::CdpInvestment);

    expect($investment)->not->toBeNull();
    expect((float) $investment->pledged_value)->toBe(4_000_000.0);
    expect((float) $investment->max_loan_amount)->toBe(2_000_000.0);
});

it('lets two investments together carry a loan neither could carry alone', function () {
    fakeCore([$this->nic => [
        'success' => true, 'status_code' => 200, 'message' => 'ok',
        'data' => ['investments' => [
            ['policy_number' => 'CDP-JFNM-1', 'investment_amount' => 4_000_000, 'status' => 'approved', 'maturity_date' => now()->addYears(5)->toDateString()],
            ['policy_number' => 'CDP-JFNM-2', 'investment_amount' => 4_000_000, 'status' => 'approved', 'maturity_date' => now()->addYears(5)->toDateString()],
        ]],
    ]]);

    $loan = loanFor($this->product, $this->customer, 3_000_000);

    $rows = writeSecurities($loan, [
        ($this->cdp)('CDP-JFNM-1'),
        ($this->cdp)('CDP-JFNM-2'),
    ], 3_000_000);

    expect($rows)->toHaveCount(2);

    app(LoanSecurityService::class)->saveCollection($loan, $rows);

    $loan->refresh()->load('securities');

    // 2,000,000 + 2,000,000
    expect($loan->securityCoverage()['total_eligible_security_value'])->toBe(4_000_000.0);
    expect($loan->securities->pluck('policy_number')->sort()->values()->all())
        ->toBe(['CDP-JFNM-1', 'CDP-JFNM-2']);
});

it('refuses the pair too when even together they fall short', function () {
    fakeCore([$this->nic => [
        'success' => true, 'status_code' => 200, 'message' => 'ok',
        'data' => ['investments' => [
            ['policy_number' => 'CDP-JFNM-1', 'investment_amount' => 4_000_000, 'status' => 'approved', 'maturity_date' => now()->addYears(5)->toDateString()],
            ['policy_number' => 'CDP-JFNM-2', 'investment_amount' => 4_000_000, 'status' => 'approved', 'maturity_date' => now()->addYears(5)->toDateString()],
        ]],
    ]]);

    $loan = loanFor($this->product, $this->customer, 5_000_000);

    // 4,000,000 of eligible value against 5,000,000 asked for.
    expect(fn () => writeSecurities($loan, [
        ($this->cdp)('CDP-JFNM-1'),
        ($this->cdp)('CDP-JFNM-2'),
    ], 5_000_000))->toThrow(SecurityLimitExceededException::class);
});

it('keeps the rule about maturity in force when a property is pledged alongside', function () {
    fakeCore([$this->nic => coreInvestment('CDP-JFNM-1', 4_000_000, now()->addMonths(6)->toDateString())]);

    $loan = loanFor($this->product, $this->customer, 1_000_000);

    // Rule 6 is about the policy rather than the loan, so it stands on its own.
    expect(fn () => writeSecurities($loan, [
        propertySecurity(['estimated_value' => 10_000_000]),
        ($this->cdp)(),
    ], 1_000_000))->toThrow(InvestmentCollateralException::class, 'matures on');
});

it('keeps the rule about ownership in force when a property is pledged alongside', function () {
    $stranger = borrower();

    // The policy is real and Core knows it, but under someone else's NIC.
    fakeCore([
        $this->nic   => coreInvestment('CDP-JFNM-1', 4_000_000),
        $stranger->id_number => coreInvestment('CDP-JFNM-9', 4_000_000),
    ]);

    $loan = loanFor($this->product, $this->customer, 1_000_000);

    expect(fn () => writeSecurities($loan, [
        propertySecurity(['estimated_value' => 10_000_000]),
        ($this->cdp)('CDP-JFNM-9', $stranger->id_number),
    ], 1_000_000))->toThrow(InvestmentCollateralException::class, 'not the NIC of');
});

it('keeps the rule about an existing hold in force when a property is pledged alongside', function () {
    $holder = loanFor($this->product, $this->customer, 1_000_000);

    LoanApplicationSecurity::create([
        'loan_application_id' => $holder->id,
        'security_type'       => 'cdp_investment',
        'policy_number'       => 'CDP-JFNM-1',
        'pledged_value'       => 4_000_000,
    ]);

    $other = loanFor($this->product, $this->customer, 1_000_000);

    expect(fn () => writeSecurities($other, [
        propertySecurity(['estimated_value' => 10_000_000]),
        ($this->cdp)(),
    ], 1_000_000))->toThrow(InvestmentCollateralException::class, 'already secures loan');
});

it('stores what Core reported, not what the request supplied', function () {
    $loan = loanFor($this->product, $this->customer, 1_000_000);

    [$row] = writeSecurities($loan, [array_merge(($this->cdp)(), [
        'pledged_value'      => 1,
        'max_loan_amount'    => 999_999_999,
        'investment_details' => ['investment_amount' => 1],
    ])], 1_000_000);

    // The figures come back from the stubbed Core call, not from the payload.
    expect((float) $row['pledged_value'])->toBe(4_000_000.0);
    expect((float) $row['max_loan_amount'])->toBe(2_000_000.0);

    expect($row['investment_details']['policy_number'])->toBe('CDP-JFNM-1');
    expect((float) $row['investment_details']['investment_amount'])->toBe(4_000_000.0);
    expect($row['investment_details']['retrieved_at'])->not->toBeNull();

    // The lookup flags describe the search rather than the investment.
    expect($row['investment_details'])->not->toHaveKey('in_use');
    expect($row['investment_details'])->not->toHaveKey('eligible');
});