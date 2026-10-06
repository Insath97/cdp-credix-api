<?php

use App\Enums\LoanApplicationStatus;
use App\Enums\LoanSecurityType;
use App\Exceptions\SecurityLimitExceededException;
use App\Models\Application;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanApplicationSecurity;
use App\Models\LoanProduct;
use App\Models\LoanType;
use App\Models\LoanTerm;
use App\Models\Setting;
use App\Services\LoanSecurityLtvService;
use App\Services\LoanSecurityService;

/*
|--------------------------------------------------------------------------
| Building blocks
|--------------------------------------------------------------------------
|
| Property and Vehicle securities are measured without leaving the process,
| so these exercise the real write path with no CDP Core stub in the way. The
| CDP Investment rules all end in an HTTP call to Core, and are left to the
| collateral tests that stub it.
|
*/

function propertySecurity(array $overrides = []): array
{
    return array_merge([
        'security_type'     => 'property_mortgage',
        'security_plan'     => 'plan_1',
        'owner_name'        => 'Nimal Perera',
        'property_type'     => 'land',
        'owner_deed_number' => 'DEED-001',
        'estimated_value'   => 10_000_000,
    ], $overrides);
}

function vehicleSecurity(array $overrides = []): array
{
    return array_merge([
        'security_type'           => 'vehicle',
        'security_plan'           => 'plan_1',
        'vehicle_make'            => 'Toyota',
        'vehicle_model'           => 'Aqua',
        'year_of_manufacture'     => 2019,
        'year_of_registration'    => 2020,
        'registration_number'     => 'CAB-1234',
        'vehicle_value'           => 3_000_000,
    ], $overrides);
}

/**
 * Configure a security type's plans, as the settings migration seeds them and an
 * administrator later edits them. Plan 1 is at what the type lends at today, so
 * a test can change it and change only what it means to change.
 *
 * @param array<string, float> $plans plan code => percentage
 */
function configurePlans(LoanSecurityType $type, array $plans): void
{
    $entries = [];

    foreach ($plans as $code => $percentage) {
        $entries[$code] = ['label' => ucwords(str_replace('_', ' ', $code)), 'percentage' => $percentage];
    }

    Setting::updateOrCreate(
        ['key' => $type->plansSetting()],
        ['value' => json_encode($entries), 'type' => 'json', 'group' => 'loan_security']
    );

    Illuminate\Support\Facades\Cache::forget('setting:' . $type->plansSetting());

    try {
        $canonicalType = $type->lendingMortgageType();
        App\Models\LendingMortgage::where('type', $canonicalType)->delete();

        foreach ($plans as $code => $percentage) {
            App\Models\LendingMortgage::create([
                'type'       => $canonicalType,
                'code'       => App\Models\LendingMortgage::generateCode($canonicalType),
                'percentage' => $percentage,
                'status'     => 'active',
            ]);
        }
    } catch (\Throwable $e) {
        // Ignored if table not created in specific unit tests
    }
}

/** An officer holding every plan permission, which is what these tests assume. */
function officerWithPlans(): App\Models\User
{
    $user = App\Models\User::factory()->create();

    foreach (['use_plan_1', 'use_plan_2', 'use_plan_3'] as $permission) {
        $created = Spatie\Permission\Models\Permission::findOrCreate($permission, 'api');
        $user->givePermissionTo($created);
    }

    return $user;
}

function securedProduct(): LoanProduct
{
    $term = LoanTerm::create([
        'title' => 'General',
        'code'  => LoanProduct::SECURED_LOAN_TERM_CODE,
    ]);

    // What makes a type "secured" is its OWN loan_term_id pointing at the
    // General term (LoanProduct::isSecuredLoanType) -- not the product's term.
    $type = LoanType::create([
        'title'       => 'Standard Borrowing',
        'code'        => LoanProduct::SECURED_LOAN_TYPE_CODE,
        'loan_term_id' => $term->id,
    ]);

    return LoanProduct::create([
        'name'                => 'Mortgage Loan',
        'code'                => 'MORTGAGE-TEST',
        'loan_type_id'        => $type->id,
        'loan_term_id'        => $term->id,
        'interest_rate'       => 10,
        'min_amount'          => 0,
        'max_amount'          => 100_000_000,
        'min_term_months'     => 1,
        'max_term_months'     => 120,
        'processing_fee_type' => 'fixed',
        'processing_fee_value' => 0,
        'is_active'           => true,
        'is_group_loan'       => false,
    ]);
}

function borrower(): Customer
{
    static $n = 0;

    return Customer::create([
        'full_name' => 'Kamal Fernando ' . ++$n,
        'id_type'   => 'nic',
        'id_number' => '1985340009' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
    ]);
}

/** Write a collection through the real service, as the controllers do. */
function writeSecurities(LoanApplication $loan, array $securities, float $requested): array
{
    return app(LoanSecurityService::class)->prepareAll(
        $securities,
        [Customer::find($loan->customer_id)],
        $requested,
        12,
        now()
    );
}

function loanFor(LoanProduct $product, Customer $customer, float $requested): LoanApplication
{
    static $n = 0;

    // loan_applications.application_id points at the applications table.
    $application = Application::create([
        'application_no'           => 'APP-' . str_pad((string) ++$n, 5, '0', STR_PAD_LEFT),
        'application_type'         => 'loan_application',
        'requested_amount'         => $requested,
        'repayment_period_months'  => 12,
    ]);

    $loan = LoanApplication::create([
        'application_id'   => $application->id,
        'customer_id'      => $customer->id,
        'loan_product_id'  => $product->id,
        'requested_amount' => $requested,
        'term_months'      => 12,
        'interest_rate'    => 10,
        'status'           => LoanApplicationStatus::Submitted->value,
        'is_active'        => true,
        'applied_at'       => now(),
    ]);

    expect($loan->requiresSecurity())->toBeTrue();

    return $loan;
}

beforeEach(function () {
    $this->ltv = app(LoanSecurityLtvService::class);

    // Plan 1 at what each type lent at before plans existed, so a test that does
    // not care about plans is reading the numbers it was written against.
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70]);
    configurePlans(LoanSecurityType::Vehicle, ['plan_1' => 70]);
    configurePlans(LoanSecurityType::CdpInvestment, ['plan_1' => 50]);

    // Writing a security needs a signed-in officer holding the plan permission.
    // Which officer is a per-test choice; this is the permissive default.
    $this->officer = officerWithPlans();
    $this->actingAs($this->officer, 'api');
});

/*
|--------------------------------------------------------------------------
| More than one security on one loan
|--------------------------------------------------------------------------
*/

it('keeps two properties on one loan, each with its own details and value', function () {
    $loan = loanFor(securedProduct(), borrower(), 12_000_000);

    $rows = writeSecurities($loan, [
        propertySecurity(['owner_deed_number' => 'DEED-001', 'estimated_value' => 10_000_000]),
        propertySecurity(['owner_name' => 'Sirimavo Silva', 'owner_deed_number' => 'DEED-002', 'estimated_value' => 8_000_000]),
    ], 12_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, $rows);

    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(2);

    $loan->refresh()->load('securities');

    expect($loan->securities->pluck('owner_deed_number')->sort()->values()->all())
        ->toBe(['DEED-001', 'DEED-002']);

    // 10M x 70% = 7M, plus 8M x 70% = 5.6M.
    expect($loan->securityCoverage()['total_eligible_security_value'])->toBe(12_600_000.0);
});

it('keeps a property and a vehicle on the same loan', function () {
    $loan = loanFor(securedProduct(), borrower(), 9_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        propertySecurity(['estimated_value' => 10_000_000]),
        vehicleSecurity(),
    ], 9_000_000));

    $loan->refresh()->load('securities');

    expect($loan->securities)->toHaveCount(2);
    expect($loan->securities->pluck('security_type')->map->value->sort()->values()->all())
        ->toBe(['property_mortgage', 'vehicle']);

    // 7,000,000 + 2,100,000
    expect($loan->securityCoverage()['total_eligible_security_value'])->toBe(9_100_000.0);
});

it('keeps two vehicles on the same loan', function () {
    $loan = loanFor(securedProduct(), borrower(), 4_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        vehicleSecurity(['registration_number' => 'CAB-1111', 'vehicle_value' => 3_000_000]),
        vehicleSecurity(['registration_number' => 'CAB-2222', 'vehicle_value' => 3_000_000]),
    ], 4_000_000));

    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(2);

    $loan->refresh()->load('securities');

    expect($loan->securities->pluck('registration_number')->sort()->values()->all())
        ->toBe(['CAB-1111', 'CAB-2222']);
});

it('carries a mixed collection, each type valued by its own rule', function () {
    $loan = loanFor(securedProduct(), borrower(), 10_000_000);

    $coverage = $this->ltv->coverage(10_000_000, [
        propertySecurity(['estimated_value' => 10_000_000]),
        propertySecurity(['owner_deed_number' => 'DEED-002', 'estimated_value' => 8_000_000]),
        vehicleSecurity(['vehicle_value' => 3_000_000]),
    ]);

    expect($coverage['security_count'])->toBe(3);
    expect($coverage['total_pledged_value'])->toBe(21_000_000.0);
    // 7,000,000 + 5,600,000 + 2,100,000
    expect($coverage['total_eligible_security_value'])->toBe(14_700_000.0);
    expect($coverage['is_sufficient'])->toBeTrue();
});

it('measures each security under its own plan, not under one rate for the loan', function () {
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70, 'plan_2' => 50]);
    configurePlans(LoanSecurityType::Vehicle, ['plan_1' => 70, 'plan_2' => 40]);

    $coverage = $this->ltv->coverage(1_000_000, [
        propertySecurity(['security_plan' => 'plan_2', 'estimated_value' => 10_000_000]),
        vehicleSecurity(['security_plan' => 'plan_1', 'vehicle_value' => 3_000_000]),
    ]);

    // The property under Plan 2: 5,000,000. The vehicle under Plan 1: 2,100,000.
    expect($coverage['total_eligible_security_value'])->toBe(7_100_000.0);
});

it('keeps a stored loan on the plan it was written under when the plans change', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70, 'plan_2' => 90]);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [propertySecurity([
        'security_plan' => 'plan_2',
        'estimated_value' => 10_000_000,
    ])], 1_000_000));

    $stored = LoanApplicationSecurity::where('loan_application_id', $loan->id)->firstOrFail();

    expect($stored->security_plan)->toBe('plan_2');
    expect((float) $stored->max_loan_percentage)->toBe(90.0);
    expect($stored->eligibleValue())->toBe(9_000_000.0);

    // Plan 2 is withdrawn from the configuration. The stored row still measures
    // at the 90% it was written against, because the snapshot on the row is what
    // a reviewed loan is held to.
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70]);

    expect($stored->fresh()->eligibleValue())->toBe(9_000_000.0);

    // Measuring it live against the configuration, the plan resolves to nothing,
    // so a security asking for it secures nothing rather than falling back.
    expect(app(LoanSecurityLtvService::class)->maxLoanPercentage(
        LoanSecurityType::PropertyMortgage,
        'plan_2'
    ))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Coverage: the loan may not be bigger than its securities carry
|--------------------------------------------------------------------------
*/

it('refuses a loan larger than the securities can carry, and says by how much', function () {
    $loan = loanFor(securedProduct(), borrower(), 18_000_000);

    try {
        writeSecurities($loan, [
            propertySecurity(['estimated_value' => 10_000_000]),
            propertySecurity(['owner_deed_number' => 'DEED-002', 'estimated_value' => 8_000_000]),
        ], 18_000_000);

        $this->fail('Expected the coverage check to refuse the loan.');
    } catch (SecurityLimitExceededException $e) {
        // 7,000,000 + 5,600,000 = 12,600,000 against 18,000,000 asked for.
        expect($e->totals['total_max_loan_amount'])->toBe(12_600_000.0);
        expect($e->totals['remaining_uncovered_amount'])->toBe(5_400_000.0);
        expect($e->getMessage())->toContain('Rs. 5,400,000.00 is still uncovered');
        expect($e->getMessage())->toContain('Property (Plan 1 at 70%): Rs. 10,000,000.00 = Rs. 7,000,000.00');
        expect($e->field)->toBe('requested_amount');
    }

    // Nothing was written: the refusal happens before the transaction.
    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(0);
});

it('accepts a loan covered exactly to the rupee', function () {
    $loan = loanFor(securedProduct(), borrower(), 12_600_000);

    $coverage = $this->ltv->coverage(12_600_000, [
        propertySecurity(['estimated_value' => 10_000_000]),
        propertySecurity(['owner_deed_number' => 'DEED-002', 'estimated_value' => 8_000_000]),
    ]);

    expect($coverage['total_eligible_security_value'])->toBe(12_600_000.0);
    expect($coverage['remaining_uncovered_amount'])->toBe(0.0);
    expect($coverage['coverage_percentage'])->toBe(100.0);
    expect($coverage['is_sufficient'])->toBeTrue();
});

it('refuses a loan one rupee above what the securities carry', function () {
    $this->ltv->assertWithinLimit(12_600_000.01, [propertySecurity(['estimated_value' => 10_000_000])]);
})->throws(SecurityLimitExceededException::class);

it('names a security that has no plan instead of showing it as worth nothing', function () {
    try {
        $this->ltv->assertWithinLimit(1_000_000, [[
            'security_type' => 'property_mortgage',
            'estimated_value' => 10_000_000,
        ]]);

        $this->fail('Expected the coverage check to refuse the loan.');
    } catch (SecurityLimitExceededException $e) {
        expect($e->getMessage())->toContain('Property: no lending plan chosen, so it secures nothing.');
    }
});

it('counts a security with no value as worth nothing rather than as an error', function () {
    $coverage = $this->ltv->coverage(1_000_000, [
        ['security_type' => 'property_mortgage'],
        vehicleSecurity(['vehicle_value' => 2_000_000]),
    ]);

    expect($coverage['security_count'])->toBe(2);
    expect($coverage['valued_security_count'])->toBe(1);
    expect($coverage['total_pledged_value'])->toBe(2_000_000.0);
    expect($coverage['total_eligible_security_value'])->toBe(1_400_000.0);
});

/*
|--------------------------------------------------------------------------
| Every security carries a value
|--------------------------------------------------------------------------
*/

it('settles a property value onto the row along with the percentage it earns', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    [$row] = writeSecurities($loan, [propertySecurity(['estimated_value' => 10_000_000])], 1_000_000);

    expect((float) $row['pledged_value'])->toBe(10_000_000.0);
    expect((float) $row['max_loan_percentage'])->toBe(70.0);
    expect((float) $row['max_loan_amount'])->toBe(7_000_000.0);
});

it('settles a vehicle value onto the row', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    [$row] = writeSecurities($loan, [vehicleSecurity(['vehicle_value' => 3_000_000])], 1_000_000);

    expect((float) $row['pledged_value'])->toBe(3_000_000.0);
    expect((float) $row['max_loan_amount'])->toBe(2_100_000.0);
});

it('reads a security back at its own value and its own eligible value', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        propertySecurity(['estimated_value' => 10_000_000]),
        vehicleSecurity(['vehicle_value' => 2_500_000]),
    ], 1_000_000));

    $loan->refresh()->load('securities');

    $property = $loan->securities->firstWhere('security_type', LoanSecurityType::PropertyMortgage);
    $vehicle  = $loan->securities->firstWhere('security_type', LoanSecurityType::Vehicle);

    expect($property->value())->toBe(10_000_000.0);
    expect($property->eligibleValue())->toBe(7_000_000.0);
    expect($vehicle->value())->toBe(2_500_000.0);
    expect($vehicle->eligibleValue())->toBe(1_750_000.0);

    expect($loan->securityCoverage())->toMatchArray([
        'security_count'                => 2,
        'valued_count'                  => 2,
        'total_pledged_value'           => 12_500_000.0,
        'total_eligible_security_value' => 8_750_000.0,
    ]);
});

it('reads a row written before pledged_value existed off its own type column', function () {
    // A property stored the old way: estimated_value, no pledged_value, and no
    // plan column either -- the backfill migration fills plan_1 for real rows.
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    $security = LoanApplicationSecurity::create([
        'loan_application_id' => $loan->id,
        'security_type'       => 'property_mortgage',
        'security_plan'       => 'plan_1',
        'estimated_value'     => 10_000_000,
    ]);

    expect($security->value())->toBe(10_000_000.0);
    expect($security->eligibleValue())->toBe(7_000_000.0);
});

it('reads a row that names no plan as unmeasured, not at a type-wide default', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    $security = LoanApplicationSecurity::create([
        'loan_application_id' => $loan->id,
        'security_type'       => 'property_mortgage',
        'estimated_value'     => 10_000_000,
    ]);

    expect($security->value())->toBe(10_000_000.0);
    expect($security->eligibleValue())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| The frontend is not authoritative for any of it
|--------------------------------------------------------------------------
*/

it('ignores a pledged value, percentage or ceiling sent by the client', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    [$row] = writeSecurities($loan, [propertySecurity([
        'estimated_value'     => 10_000_000,
        // What a client would send hoping to borrow against nothing.
        'pledged_value'       => 1,
        'max_loan_percentage' => 100,
        'max_loan_amount'     => 999_999_999,
        'security_plan'       => 'plan_1',
    ])], 1_000_000);

    expect((float) $row['pledged_value'])->toBe(10_000_000.0);
    expect((float) $row['max_loan_percentage'])->toBe(70.0);
    expect((float) $row['max_loan_amount'])->toBe(7_000_000.0);
});

it('refuses a plan the signed-in officer has no permission to use', function () {
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70, 'plan_2' => 90]);

    // An officer who holds only Plan 1.
    $limited = App\Models\User::factory()->create();
    $limited->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('use_plan_1', 'api'));

    $this->actingAs($limited, 'api');

    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    try {
        writeSecurities($loan, [propertySecurity(['security_plan' => 'plan_2'])], 1_000_000);

        $this->fail('Expected the plan permission to refuse the write.');
    } catch (App\Exceptions\SecurityPlanException $e) {
        expect($e->getMessage())->toContain('You do not have permission to use Plan 2');
        expect($e->getMessage())->toContain('use_plan_2');
        expect($e->status)->toBe(403);
        expect($e->field)->toBe('security_plan');

        // Only the plans this officer may use come back with the refusal, so the
        // wizard re-renders with something they can pick.
        expect(array_column($e->plans, 'code'))->toBe(['plan_1']);
    }

    // Nothing was written, so the refused plan never reaches the database.
    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(0);
});

it('refuses a plan that is not one the type offers', function () {
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70]);

    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    expect(fn () => writeSecurities($loan, [propertySecurity(['security_plan' => 'plan_7'])], 1_000_000))
        ->toThrow(App\Exceptions\SecurityPlanException::class, 'not a Property lending plan');
});

it('refuses a security with no plan at all', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    expect(fn () => writeSecurities($loan, [propertySecurity(['security_plan' => null])], 1_000_000))
        ->toThrow(App\Exceptions\SecurityPlanException::class, 'Select a lending plan');
});

it('measures a stored loan without asking the reviewer for the plan permission', function () {
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70, 'plan_2' => 90]);

    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [propertySecurity([
        'security_plan' => 'plan_2',
        'estimated_value' => 10_000_000,
    ])], 1_000_000));

    // A reviewer who holds no plan permission at all must still be able to
    // measure what they are reviewing: the plan is read from the row, and the
    // submitter's permission is not re-checked on a stored loan.
    $reviewer = App\Models\User::factory()->create();
    $this->actingAs($reviewer, 'api');

    $stored = LoanApplicationSecurity::where('loan_application_id', $loan->id)->firstOrFail();

    expect(app(LoanSecurityLtvService::class)->maxLoanPercentage(
        LoanSecurityType::PropertyMortgage,
        $stored->security_plan
    ))->toBe(90.0);

    expect($stored->eligibleValue())->toBe(9_000_000.0);
});

it('offers an officer only the plans they may use', function () {
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70, 'plan_2' => 90, 'plan_3' => 60]);

    $limited = App\Models\User::factory()->create();
    $limited->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate('use_plan_1', 'api'));

    $this->actingAs($limited, 'api');

    $plans = app(App\Services\LoanSecurityPlanService::class)->plansFor(LoanSecurityType::PropertyMortgage);

    expect($plans)->toHaveCount(3);
    expect(array_column($plans, 'allowed'))->toBe([true, false, false]);
    expect($plans[1]['permission'])->toBe('use_plan_2');
});

it('refuses a loan padded out with a client-invented percentage', function () {
    $loan = loanFor(securedProduct(), borrower(), 9_000_000);

    expect(fn () => writeSecurities($loan, [propertySecurity([
        'estimated_value'     => 10_000_000,
        'max_loan_percentage' => 100,
    ])], 9_000_000))->toThrow(SecurityLimitExceededException::class);
});

/*
|--------------------------------------------------------------------------
| Replacing the list
|--------------------------------------------------------------------------
*/

it('removes only the security left out of the list, keeping the rest', function () {
    // Asked for 2,000,000 so that the vehicle on its own (2,100,000) still
    // carries the loan once the property is taken off the list.
    $loan = loanFor(securedProduct(), borrower(), 2_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        propertySecurity(['owner_deed_number' => 'DEED-001', 'estimated_value' => 10_000_000]),
        vehicleSecurity(['registration_number' => 'CAB-1234']),
    ], 2_000_000));

    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(2);

    // The property is taken off the list; the vehicle stays.
    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        vehicleSecurity(['registration_number' => 'CAB-1234']),
    ], 2_000_000));

    $remaining = LoanApplicationSecurity::where('loan_application_id', $loan->id)->get();

    expect($remaining)->toHaveCount(1);
    expect($remaining->first()->registration_number)->toBe('CAB-1234');
});

it('refuses to drop the securities that were carrying the loan', function () {
    $loan = loanFor(securedProduct(), borrower(), 5_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        propertySecurity(['estimated_value' => 10_000_000]),
        vehicleSecurity(),
    ], 5_000_000));

    // Removing the property leaves the vehicle worth 2,100,000 against 5,000,000
    // asked for. The replacement is refused rather than written.
    expect(fn () => writeSecurities($loan, [vehicleSecurity()], 5_000_000))
        ->toThrow(SecurityLimitExceededException::class);

    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(2);
});

it('takes every security off the loan when the list goes to none', function () {
    $loan = loanFor(securedProduct(), borrower(), 5_000_000);

    app(LoanSecurityService::class)->saveCollection($loan, writeSecurities($loan, [
        propertySecurity(),
        vehicleSecurity(),
    ], 5_000_000));

    $loan->securities()->delete();

    expect(LoanApplicationSecurity::where('loan_application_id', $loan->id)->count())->toBe(0);
    expect($loan->fresh()->securityCoverage())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| One investment cannot be pledged twice
|--------------------------------------------------------------------------
*/

it('refuses the same policy named twice in one list, however it is written', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    $rows = [
        ['security_type' => 'cdp_investment', 'policy_number' => 'CDP-JFNM-00000138'],
        ['security_type' => 'cdp_investment', 'policy_number' => '  cdp-jfnm-00000138  '],
    ];

    expect(fn () => app(LoanSecurityService::class)->assertPoliciesStillFree($rows, $loan->id))
        ->toThrow(App\Exceptions\InvestmentCollateralException::class, 'pledged twice');
});

it('finds a policy held by a live loan wherever it sits on that loan', function () {
    $product = securedProduct();
    $first  = borrower();

    $holder = loanFor($product, $first, 1_000_000);
    LoanApplicationSecurity::create([
        'loan_application_id' => $holder->id,
        'security_type'       => 'cdp_investment',
        'policy_number'       => 'CDP-JFNM-00000138',
        'pledged_value'       => 2_000_000,
    ]);

    $second = loanFor($product, borrower(), 1_000_000);

    expect(LoanApplication::holdingPolicy('CDP-JFNM-00000138')->pluck('id')->all())
        ->toBe([$holder->id]);

    expect(fn () => app(LoanSecurityService::class)->assertPoliciesStillFree(
        [['security_type' => 'cdp_investment', 'policy_number' => 'CDP-JFNM-00000138']],
        $second->id
    ))->toThrow(App\Exceptions\InvestmentCollateralException::class);
});

it('frees a policy once the loan holding it is no longer live', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    LoanApplicationSecurity::create([
        'loan_application_id' => $loan->id,
        'security_type'       => 'cdp_investment',
        'policy_number'       => 'CDP-JFNM-00000138',
    ]);

    $loan->update(['status' => LoanApplicationStatus::Cancelled->value]);

    expect(LoanApplication::holdingPolicy('CDP-JFNM-00000138')->count())->toBe(0);

    // Passing means no exception is thrown: the policy is up for grabs again.
    app(LoanSecurityService::class)->assertPoliciesStillFree(
        [['security_type' => 'cdp_investment', 'policy_number' => 'CDP-JFNM-00000138']],
        null
    );

    expect(true)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| What the Loan Security step is told
|--------------------------------------------------------------------------
*/

it('tells the step each type its value field and the plans it may use', function () {
    $options = app(App\Services\LoanSecurityLtvService::class);

    expect(LoanSecurityType::CdpInvestment->valueField())->toBeNull();
    expect(LoanSecurityType::PropertyMortgage->valueField())->toBe('estimated_value');
    expect(LoanSecurityType::Vehicle->valueField())->toBe('vehicle_value');

    foreach (LoanSecurityType::cases() as $type) {
        expect($options->maxLoanPercentage($type, 'plan_1'))->toBeFloat();

        // A plan that is not offered measures as nothing rather than at a
        // type-wide default.
        expect($options->maxLoanPercentage($type, 'plan_9'))->toBeNull();
        expect($options->maxLoanPercentage($type, null))->toBeNull();
    }
});

it('gives the step a helper line per security and a coverage total', function () {
    $rows = $this->ltv->breakdown([
        propertySecurity(['estimated_value' => 10_000_000]),
        vehicleSecurity(['vehicle_value' => 3_000_000]),
    ]);

    expect($rows[0]['helper_text'])
        ->toBe('Plan 1 (70%) -- Based on the property valuation, the maximum loan amount for this security is Rs. 7,000,000.00.');

    expect($rows[1]['helper_text'])
        ->toBe('Plan 1 (70%) -- Based on the vehicle value, the maximum loan amount for this security is Rs. 2,100,000.00.');

    expect($rows[0]['value_field'])->toBe('estimated_value');
    expect($rows[1]['value_field'])->toBe('vehicle_value');
    expect($rows[0]['security_plan'])->toBe('plan_1');
    expect($rows[0]['security_plan_permission'])->toBe('use_plan_1');
});

it('asks for a value before it will show a ceiling', function () {
    [$row] = $this->ltv->breakdown([[
        'security_type' => 'property_mortgage',
        'security_plan' => 'plan_1',
    ]]);

    expect($row['value'])->toBeNull();
    expect($row['max_loan_amount'])->toBeNull();
    expect($row['helper_text'])
        ->toBe('Plan 1 (70%) -- Enter the property valuation to see the maximum loan amount for this security.');
});

it('says a plan must be chosen rather than quoting a rate nobody picked', function () {
    [$row] = $this->ltv->breakdown([[
        'security_type' => 'property_mortgage',
        'estimated_value' => 10_000_000,
    ]]);

    expect($row['security_plan'])->toBeNull();
    expect($row['max_loan_percentage'])->toBeNull();
    expect($row['max_loan_amount'])->toBeNull();
    expect($row['helper_text'])
        ->toBe('Select a lending plan for this Property -- the limit is set by the plan, and no plan has been chosen.');
});

it('uses the percentage the plan says, not the one another plan says', function () {
    configurePlans(LoanSecurityType::PropertyMortgage, ['plan_1' => 70, 'plan_2' => 85]);

    $coverage = $this->ltv->coverage(1_000_000, [
        propertySecurity(['security_plan' => 'plan_1', 'estimated_value' => 10_000_000]),
    ]);
    expect($coverage['total_eligible_security_value'])->toBe(7_000_000.0);

    $coverage = $this->ltv->coverage(1_000_000, [
        propertySecurity(['security_plan' => 'plan_2', 'estimated_value' => 10_000_000]),
    ]);
    expect($coverage['total_eligible_security_value'])->toBe(8_500_000.0);
});

/*
|--------------------------------------------------------------------------
| A CDP Investment is worth what Core says it is worth
|--------------------------------------------------------------------------
*/

it('values an investment at the figure in the Core snapshot, not at anything entered', function () {
    $loan = loanFor(securedProduct(), borrower(), 1_000_000);

    $coverage = $this->ltv->coverage(1_000_000, [[
        'security_type'      => 'cdp_investment',
        'security_plan'      => 'plan_1',
        'investment_details' => ['investment_amount' => 4_000_000, 'policy_number' => 'CDP-JFNM-1'],
    ]]);

    expect($coverage['total_pledged_value'])->toBe(4_000_000.0);
    expect($coverage['total_eligible_security_value'])->toBe(2_000_000.0); // at the seeded 50%

    expect($this->ltv->breakdown([[
        'security_type'      => 'cdp_investment',
        'security_plan'      => 'plan_1',
        'investment_details' => ['investment_amount' => 4_000_000],
    ]])[0]['value_field'])->toBeNull();
});

it('values an investment of unknown worth as unmeasured, not as worthless', function () {
    $coverage = $this->ltv->coverage(1_000_000, [[
        'security_type'      => 'cdp_investment',
        'security_plan'      => 'plan_1',
        'investment_details' => ['policy_number' => 'CDP-JFNM-1'],
    ]]);

    expect($coverage['valued_security_count'])->toBe(0);
    expect($coverage['total_eligible_security_value'])->toBe(0.0);
    expect($coverage['is_sufficient'])->toBeFalse();
});
