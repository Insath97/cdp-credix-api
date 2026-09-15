<?php

use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\Payment;
use App\Models\RecoveryCase;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
|
| These are the numbers people print and act on, so the two things that matter
| are that the figures are right and that an officer only ever sees their own
| branch's. A report that quietly counts the whole company reads exactly like
| one that does not.
|
| Every figure here is asserted against an amount the test itself created, so a
| change to an aggregate shows up as a wrong number rather than a passing test.
|
*/

/**
 * The product every loan needs.
 *
 * loan_applications.loan_product_id is NOT NULL, and a product in turn needs a
 * loan type and a loan term, so the whole chain has to exist before any report
 * has a loan to count. Made once per test and reused.
 */
function reportLoanProduct(): LoanProduct
{
    static $product = null;

    if ($product && LoanProduct::whereKey($product->id)->exists()) {
        return $product;
    }

    $term = LoanTerm::create(['code' => 'MONTHLY', 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'STD', 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return $product = LoanProduct::create([
        'name'                 => 'Test Product',
        'code'                 => 'TP-001',
        'loan_type_id'         => $type->id,
        'loan_term_id'         => $term->id,
        'interest_rate'        => 10,
        'interest_type'        => 'flat',
        'min_amount'           => 1000,
        'max_amount'           => 1000000,
        'min_term_months'      => 1,
        'max_term_months'      => 60,
        'processing_fee_type'  => 'fixed',
        'processing_fee_value' => 0,
        'is_active'            => true,
    ]);
}

/**
 * A customer, a disbursed loan, and optionally a payment against it.
 *
 * Returns the loan so a test can put it into whatever state it needs.
 */
function loanFor(Branch $branch, array $overrides = []): LoanApplication
{
    $customer = Customer::create(array_merge([
        'full_name'          => 'Test Borrower',
        'name_with_initials' => 'T. Borrower',
        'id_type'            => 'nic',
        'id_number'          => 'NIC' . fake()->unique()->numerify('#########'),
        'address_line_1'     => '1 Test Lane',
        'country'            => 'Sri Lanka',
        'date_of_birth'      => '1990-01-01',
        'phone_primary'      => '0770000000',
        'have_whatsapp'      => false,
        'preferred_language' => 'en',
        'branch_id'          => $branch->id,
    ], $overrides['customer'] ?? []));

    $application = Application::create([
        'application_type' => 'loan',
        'branch'           => $branch->name,
        'requested_amount' => 100000,
    ]);

    return LoanApplication::create(array_merge([
        'application_id'      => $application->id,
        'loan_product_id'     => reportLoanProduct()->id,
        'customer_id'         => $customer->id,
        'branch_id'           => $branch->id,
        'requested_amount'    => 100000,
        'approved_amount'     => 100000,
        'interest_rate'       => 10,
        'term_months'         => 12,
        'outstanding_balance' => 110000,
        'status'              => LoanApplicationStatus::Active,
        'applied_at'          => now(),
        'disbursed_at'        => now(),
    ], $overrides['loan'] ?? []));
}

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication on every report', function () {
    foreach (['branch-wise', 'customer-wise', 'loan-portfolio', 'recovery'] as $report) {
        $this->getJson($this->api("/reports/{$report}"))->assertStatus(401);
    }

    $this->getJson($this->api('/reports/recovery/1'))->assertStatus(401);
});

it('refuses every report to a signed-in user without Report Index', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    foreach (['branch-wise', 'customer-wise', 'loan-portfolio', 'recovery'] as $report) {
        $this->getJson($this->api("/reports/{$report}"))->assertStatus(403);
    }
});

/*
|--------------------------------------------------------------------------
| Branch-wise
|--------------------------------------------------------------------------
*/

it('totals disbursement, portfolio and collections per branch', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $loan = loanFor($chain['branch']);

    Payment::create([
        'loan_application_id' => $loan->id,
        'customer_id'         => $loan->customer_id,
        'amount'              => 25000,
        'payment_method'      => 'cash',
        'paid_at'             => now(),
        'receipt_no'          => 'RCP-1',
    ]);

    $response = $this->getJson($this->api('/reports/branch-wise'));

    $response->assertStatus(200);

    $row = collect($response->json('data.data'))->firstWhere('branch_id', $chain['branch']->id);

    expect($row['applications_count'])->toBe(1);
    expect((float) $row['total_disbursed'])->toBe(100000.0);
    expect((float) $row['outstanding_portfolio'])->toBe(110000.0);
    expect((float) $row['total_collected'])->toBe(25000.0);
});

it('counts the summary over every branch the filters admit, not just the page', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    loanFor($chain['branch']);

    $response = $this->getJson($this->api('/reports/branch-wise?per_page=1'));

    $response->assertStatus(200);
    expect((float) $response->json('summary.total_disbursed'))->toBe(100000.0);
});

it('leaves a loan disbursed outside the window out of the disbursed total', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    loanFor($chain['branch'], ['loan' => ['disbursed_at' => now()->subMonths(6)]]);

    $response = $this->getJson($this->api('/reports/branch-wise'));

    $row = collect($response->json('data.data'))->firstWhere('branch_id', $chain['branch']->id);

    expect((float) $row['total_disbursed'])->toBe(0.0);
});

it('counts a loan disbursed on the last day of the window', function () {
    // The window's end is stamped endOfDay, so a loan disbursed at any time on
    // the closing date belongs to it. Truncating to midnight would drop a whole
    // day of disbursement off the end of every monthly report.
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    loanFor($chain['branch'], ['loan' => ['disbursed_at' => now()->setTime(23, 30)]]);

    $response = $this->getJson($this->api(
        '/reports/branch-wise?start_date=' . now()->toDateString() . '&end_date=' . now()->toDateString()
    ));

    $row = collect($response->json('data.data'))->firstWhere('branch_id', $chain['branch']->id);

    expect((float) $row['total_disbursed'])->toBe(100000.0);
});

/*
|--------------------------------------------------------------------------
| Branch confinement
|--------------------------------------------------------------------------
|
| An officer posted to a branch sees their branch and nothing else, whatever
| they put in the request.
|
*/

it('confines a branch officer to their own branch', function () {
    $chain = $this->organisationChain();

    $other = Branch::create([
        'name' => 'Kandy', 'code' => 'KDY-001', 'address_line1' => '2 Hill St', 'city' => 'Kandy',
        'zone_id' => $chain['zonal']->id, 'region_id' => $chain['region']->id,
        'province_id' => $chain['province']->id, 'phone_primary' => '0812222222',
        'opening_date' => '2021-01-01', 'branch_type' => 'city', 'is_active' => true,
    ]);

    loanFor($chain['branch']);
    loanFor($other);

    $officer = $this->officerAtBranch($chain['branch'], ['Report Index']);
    $this->actingAsApi($officer);

    $response = $this->getJson($this->api('/reports/branch-wise'));

    $response->assertStatus(200);

    $ids = collect($response->json('data.data'))->pluck('branch_id')->all();
    expect($ids)->toBe([$chain['branch']->id]);
});

it('ignores a branch_id an officer sends for someone else branch', function () {
    $chain = $this->organisationChain();

    $other = Branch::create([
        'name' => 'Kandy', 'code' => 'KDY-001', 'address_line1' => '2 Hill St', 'city' => 'Kandy',
        'zone_id' => $chain['zonal']->id, 'region_id' => $chain['region']->id,
        'province_id' => $chain['province']->id, 'phone_primary' => '0812222222',
        'opening_date' => '2021-01-01', 'branch_type' => 'city', 'is_active' => true,
    ]);

    loanFor($other);

    $this->actingAsApi($this->officerAtBranch($chain['branch'], ['Report Index']));

    $response = $this->getJson($this->api('/reports/branch-wise?branch_id=' . $other->id));

    $ids = collect($response->json('data.data'))->pluck('branch_id')->all();
    expect($ids)->toBe([$chain['branch']->id]);
});

it('lets head office filter to one branch', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $response = $this->getJson($this->api('/reports/branch-wise?branch_id=' . $chain['branch']->id));

    $ids = collect($response->json('data.data'))->pluck('branch_id')->all();
    expect($ids)->toBe([$chain['branch']->id]);
});

/*
|--------------------------------------------------------------------------
| Customer-wise and portfolio
|--------------------------------------------------------------------------
*/

it('reports a customer borrowing and repayment', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $loan = loanFor($chain['branch']);

    Payment::create([
        'loan_application_id' => $loan->id,
        'customer_id'         => $loan->customer_id,
        'amount'              => 30000,
        'payment_method'      => 'cash',
        'paid_at'             => now(),
        'receipt_no'          => 'RCP-2',
    ]);

    $response = $this->getJson($this->api('/reports/customer-wise'));

    $response->assertStatus(200);

    $row = collect($response->json('data.data'))->firstWhere('customer_id', $loan->customer_id);

    expect($row)->not->toBeNull();
    expect((float) ($row['total_paid'] ?? $row['total_collected']))->toBe(30000.0);
});

it('lists a loan on the portfolio report', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $loan = loanFor($chain['branch']);

    $response = $this->getJson($this->api('/reports/loan-portfolio'));

    $response->assertStatus(200);

    $ids = collect($response->json('data.data'))->pluck('id')->all();
    expect($ids)->toContain($loan->id);
});

it('filters the portfolio report by status', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    loanFor($chain['branch']);
    loanFor($chain['branch'], ['loan' => ['status' => LoanApplicationStatus::Closed]]);

    $response = $this->getJson($this->api('/reports/loan-portfolio?status=' . LoanApplicationStatus::Closed->value));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| Recovery
|--------------------------------------------------------------------------
*/

it('shows a recovery case detail with its activity timeline', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $loan = loanFor($chain['branch']);

    $case = RecoveryCase::create([
        'loan_application_id' => $loan->id,
        'customer_id'         => $loan->customer_id,
        'case_no'             => 'RC-' . fake()->unique()->numerify('######'),
        'status'              => 'open',
        'stage'               => 'internal',
        'overdue_amount'      => 15000,
        'opened_at'           => now(),
    ]);

    $response = $this->getJson($this->api('/reports/recovery/') . $case->id);

    $response->assertStatus(200);
    expect($response->json('data.activities'))->toBeArray();
});

it('returns 404 for a recovery case that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $this->getJson($this->api('/reports/recovery/999999'))->assertStatus(404);
});

it('does not let an officer open another branch recovery case', function () {
    // recovery() confines its rows to the officer's branch. The drill-down
    // behind each row must confine the same way, or the confinement is a
    // listing filter rather than an access rule and the whole case -- customer,
    // overdue amount, every agent note -- is one guessed id away.
    $chain = $this->organisationChain();

    $other = Branch::create([
        'name' => 'Kandy', 'code' => 'KDY-001', 'address_line1' => '2 Hill St', 'city' => 'Kandy',
        'zone_id' => $chain['zonal']->id, 'region_id' => $chain['region']->id,
        'province_id' => $chain['province']->id, 'phone_primary' => '0812222222',
        'opening_date' => '2021-01-01', 'branch_type' => 'city', 'is_active' => true,
    ]);

    $foreignLoan = loanFor($other);

    $case = RecoveryCase::create([
        'loan_application_id' => $foreignLoan->id,
        'customer_id'         => $foreignLoan->customer_id,
        'case_no'             => 'RC-' . fake()->unique()->numerify('######'),
        'status'              => 'open',
        'stage'               => 'internal',
        'overdue_amount'      => 15000,
        'opened_at'           => now(),
    ]);

    $this->actingAsApi($this->officerAtBranch($chain['branch'], ['Report Index']));

    // It is not theirs to see, and 404 rather than 403 so the endpoint cannot
    // be used to confirm which case ids exist.
    $this->getJson($this->api('/reports/recovery/') . $case->id)->assertStatus(404);
});

it('keeps another branch cases out of the recovery listing', function () {
    $chain = $this->organisationChain();

    $other = Branch::create([
        'name' => 'Kandy', 'code' => 'KDY-001', 'address_line1' => '2 Hill St', 'city' => 'Kandy',
        'zone_id' => $chain['zonal']->id, 'region_id' => $chain['region']->id,
        'province_id' => $chain['province']->id, 'phone_primary' => '0812222222',
        'opening_date' => '2021-01-01', 'branch_type' => 'city', 'is_active' => true,
    ]);

    $foreignLoan = loanFor($other);

    RecoveryCase::create([
        'loan_application_id' => $foreignLoan->id,
        'customer_id'         => $foreignLoan->customer_id,
        'case_no'             => 'RC-' . fake()->unique()->numerify('######'),
        'status'              => 'open',
        'stage'               => 'internal',
        'overdue_amount'      => 15000,
        'opened_at'           => now(),
    ]);

    $this->actingAsApi($this->officerAtBranch($chain['branch'], ['Report Index']));

    $response = $this->getJson($this->api('/reports/recovery'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Inputs
|--------------------------------------------------------------------------
*/

it('rejects a start or end date that is not a date', function () {
    // Carbon::parse() throws on nonsense, and the throw lands in the catch-all,
    // so a mistyped date used to come back as an opaque 500 rather than a 422
    // naming the field.
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $this->assertValidationFailed(
        $this->getJson($this->api('/reports/branch-wise?start_date=not-a-date')),
        ['start_date']
    );
});

it('rejects a window that ends before it starts', function () {
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $this->assertValidationFailed(
        $this->getJson($this->api('/reports/branch-wise?start_date=2026-06-30&end_date=2026-06-01')),
        ['end_date']
    );
});

it('does not run more queries as more customers appear', function () {
    // The guard against the row aggregates coming back. Each row used to carry
    // five of its own queries, so a hundred-row page cost five hundred round
    // trips and the report degraded with every customer registered. Counting
    // the queries for one customer and for five proves the figures are gathered
    // per page rather than per row.
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $count = function () {
        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });
        $this->getJson($this->api('/reports/customer-wise'))->assertStatus(200);

        return $queries;
    };

    loanFor($chain['branch']);
    $withOne = $count();

    foreach (range(1, 4) as $ignored) {
        loanFor($chain['branch']);
    }
    $withFive = $count();

    // Not equality: the first request through also warms the permission cache,
    // so it legitimately costs a little more. What matters is that five times
    // the rows did not cost five times the queries -- with the old per-row
    // aggregates this second figure was about twenty higher, not lower.
    expect($withFive)->toBeLessThanOrEqual($withOne);
});

it('does not fall over on a per_page of zero', function () {
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $this->getJson($this->api('/reports/branch-wise?per_page=0'))->assertStatus(200);
});

it('caps an oversized per_page', function () {
    $this->actingAsApi($this->userWithPermissions(['Report Index']));

    $response = $this->getJson($this->api('/reports/branch-wise?per_page=100000'));

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(100);
});
