<?php

use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\CustomerCreditScore;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\Setting;
use App\Services\CreditScoreService;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Credit score
|--------------------------------------------------------------------------
|
| An installment settled by its deadline earns the on-time points; one settled
| after it loses the late penalty. Both figures, and the grace period that
| decides where the deadline falls, are System Settings rather than constants,
| so the rule is only correct if changing the setting actually changes the
| score.
|
| Every expectation below is written as the arithmetic it stands for, so a
| change to the formula shows up as a wrong number rather than a passing test.
|
*/

function creditProduct(): LoanProduct
{
    static $product = null;

    if ($product && LoanProduct::whereKey($product->id)->exists()) {
        return $product;
    }

    $term = LoanTerm::create(['code' => 'CS-M', 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'CS-STD', 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return $product = LoanProduct::create([
        'name' => 'Credit Score Product', 'code' => 'CSP-001',
        'loan_type_id' => $type->id, 'loan_term_id' => $term->id,
        'interest_rate' => 10, 'interest_type' => 'flat',
        'min_amount' => 1000, 'max_amount' => 1000000,
        'min_term_months' => 1, 'max_term_months' => 60,
        'processing_fee_type' => 'fixed', 'processing_fee_value' => 0,
        'is_active' => true,
    ]);
}

/**
 * A customer with one disbursed loan, ready to hang installments off.
 *
 * @return array{customer: Customer, loan: LoanApplication}
 */
function creditLoan(array $loanOverrides = []): array
{
    $customer = Customer::create([
        'full_name' => 'Score Borrower', 'name_with_initials' => 'S. Borrower',
        'id_type' => 'nic', 'id_number' => 'CS' . fake()->unique()->numerify('##########'),
        'address_line_1' => '1 Test Lane', 'country' => 'Sri Lanka',
        'date_of_birth' => '1990-01-01', 'phone_primary' => '0744125923',
        'have_whatsapp' => false, 'preferred_language' => 'en',
    ]);

    $application = Application::create([
        'application_type' => 'loan', 'requested_amount' => 120000,
    ]);

    $loan = LoanApplication::create(array_merge([
        'application_id'   => $application->id,
        'loan_product_id'  => creditProduct()->id,
        'customer_id'      => $customer->id,
        'requested_amount' => 120000,
        'approved_amount'  => 120000,
        'interest_rate'    => 10,
        'term_months'      => 12,
        'status'           => LoanApplicationStatus::Active,
        'applied_at'       => now()->subYear(),
        'disbursed_at'     => now()->subYear(),
    ], $loanOverrides));

    return ['customer' => $customer, 'loan' => $loan];
}

/**
 * One installment in a known state.
 *
 * `balance` is what evaluateInstallment() treats as the truth about whether a
 * month is settled, so it is derived here rather than passed in.
 */
function creditInstallment(LoanApplication $loan, int $customerId, int $no, string $dueDate, ?string $paidOn, string $status = 'paid'): LoanInstallment
{
    $amount = 10000;
    $paid = $paidOn !== null ? $amount : 0;

    return LoanInstallment::create([
        'loan_application_id' => $loan->id,
        'customer_id'         => $customerId,
        'installment_no'      => $no,
        'due_date'            => $dueDate,
        'amount_due'          => $amount,
        'amount_paid'         => $paid,
        'penalty_amount'      => 0,
        'balance'             => $amount - $paid,
        'status'              => $status,
        'paid_at'             => $paidOn,
    ]);
}

/**
 * Write a scoring setting and clear everything that memoises it.
 *
 * Two caches sit in front of these: Setting::get() remembers the value for the
 * life of the cache, and CreditScoreService keeps its own config snapshot for
 * the request. A test that changed a setting without clearing both would score
 * against the old numbers and pass for the wrong reason.
 */
function creditSetting(string $key, $value): void
{
    Setting::where('key', $key)->update(['value' => (string) $value]);

    Cache::forget("setting:{$key}");
    app(CreditScoreService::class)->forgetConfig();
}

function scoreFor(LoanApplication $loan, int $customerId): ?CustomerCreditScore
{
    app(CreditScoreService::class)->recomputeForLoan($loan->fresh());

    return CustomerCreditScore::where('loan_application_id', $loan->id)
        ->where('customer_id', $customerId)
        ->first();
}

/*
|--------------------------------------------------------------------------
| The two verdicts
|--------------------------------------------------------------------------
*/

it('awards the on-time points for an installment settled before its due date', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-05');

    $score = scoreFor($loan, $customer->id);

    // One punctual month at the seeded 10 points.
    expect($score->on_time_count)->toBe(1);
    expect($score->late_count)->toBe(0);
    expect((float) $score->final_score)->toBe(10.0);
});

it('awards the on-time points for one settled exactly on the due date', function () {
    // The deadline is the due date itself when grace is zero, and paying on the
    // day is paying on time. An off-by-one here would punish every borrower who
    // pays on the day they were told to.
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-10');

    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(10.0);
});

it('deducts the late penalty for an installment settled after its due date', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-11');

    $score = scoreFor($loan, $customer->id);

    expect($score->on_time_count)->toBe(0);
    expect($score->late_count)->toBe(1);
    expect((float) $score->final_score)->toBe(-10.0);
});

it('adds the months up across a mixed record', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    // Three punctual, two late.
    creditInstallment($loan, $customer->id, 1, '2026-01-10', '2026-01-09');
    creditInstallment($loan, $customer->id, 2, '2026-02-10', '2026-02-10');
    creditInstallment($loan, $customer->id, 3, '2026-03-10', '2026-03-01');
    creditInstallment($loan, $customer->id, 4, '2026-04-10', '2026-04-20');
    creditInstallment($loan, $customer->id, 5, '2026-05-10', '2026-05-11');

    $score = scoreFor($loan, $customer->id);

    // (3 x +10) + (2 x -10) = 10
    expect($score->on_time_count)->toBe(3);
    expect($score->late_count)->toBe(2);
    expect($score->installments_counted)->toBe(5);
    expect((float) $score->final_score)->toBe(10.0);
});

/*
|--------------------------------------------------------------------------
| The settings really are the rule
|--------------------------------------------------------------------------
*/

it('uses the on-time points figure from settings, not a constant', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-05');

    creditSetting('credit_score_on_time_points', 25);

    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(25.0);
});

it('uses the late penalty figure from settings, not a constant', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-20');

    creditSetting('credit_score_late_penalty_points', 40);

    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(-40.0);
});

it('lets the two figures differ so lateness can cost more than punctuality earns', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-01-10', '2026-01-05');
    creditInstallment($loan, $customer->id, 2, '2026-02-10', '2026-02-25');

    creditSetting('credit_score_on_time_points', 5);
    creditSetting('credit_score_late_penalty_points', 20);

    // (1 x +5) + (1 x -20) = -15
    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(-15.0);
});

it('moves the deadline by the grace days setting', function () {
    // Paid five days after the due date. With no grace that is late; with a
    // week of grace it is punctual. Same row, opposite verdict, decided
    // entirely by the setting.
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-15');

    creditSetting('credit_score_grace_days', 0);
    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(-10.0);

    creditSetting('credit_score_grace_days', 7);
    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(10.0);
});

it('treats the last day of the grace window as on time', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-15');

    creditSetting('credit_score_grace_days', 5);

    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(10.0);
});

it('treats the day after the grace window as late', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, '2026-03-10', '2026-03-16');

    creditSetting('credit_score_grace_days', 5);

    expect((float) scoreFor($loan, $customer->id)->final_score)->toBe(-10.0);
});

/*
|--------------------------------------------------------------------------
| Months that say nothing yet
|--------------------------------------------------------------------------
*/

it('does not count a month that is not due yet', function () {
    // An unpaid installment with time left says nothing about the borrower, so
    // it must not be scored AND must not enter the divisor -- otherwise every
    // new loan would start out looking delinquent.
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, now()->addMonth()->toDateString(), null, 'upcoming');

    expect(scoreFor($loan, $customer->id)->installments_counted)->toBe(0);
});

it('penalises a month that is unpaid and past its deadline', function () {
    // Without this a borrower who simply never pays would never lose a point.
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, now()->subMonths(2)->toDateString(), null, 'overdue');

    $score = scoreFor($loan, $customer->id);

    expect($score->late_count)->toBe(1);
    expect((float) $score->final_score)->toBe(-10.0);
});

it('leaves unpaid overdue months out when the setting says so', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    creditInstallment($loan, $customer->id, 1, now()->subMonths(2)->toDateString(), null, 'overdue');

    creditSetting('credit_score_count_unpaid_overdue', 0);

    expect(scoreFor($loan, $customer->id)->installments_counted)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| The band and the customer aggregate
|--------------------------------------------------------------------------
*/

it('rolls every loan into one customer figure', function () {
    ['customer' => $customer, 'loan' => $first] = creditLoan();

    creditInstallment($first, $customer->id, 1, '2026-01-10', '2026-01-05');
    creditInstallment($first, $customer->id, 2, '2026-02-10', '2026-02-05');

    $service = app(CreditScoreService::class);
    $service->recomputeForCustomer($customer->id);

    // Two punctual months at 10 points each.
    expect((float) $customer->fresh()->credit_score)->toBe(20.0);
    expect((float) $customer->fresh()->credit_score_on_time_rate)->toBe(100.0);
});

it('reads the band off the on-time share rather than the points', function () {
    // The band is a share out of 100. Handing it the signed point total instead
    // reads a perfectly ordinary record as the worst possible one: 5 punctual
    // and 4 late is 10 points, which lands in very_poor, while the true share
    // of 55.6% is fair.
    $service = app(CreditScoreService::class);

    expect($service->band($service->share(5, 9)))->toBe('fair');
    expect($service->band(10.0))->toBe('very_poor');
});

it('agrees between the show and recompute endpoints for the same customer', function () {
    ['customer' => $customer, 'loan' => $loan] = creditLoan();

    // Five punctual and four late: 10 points, but a 55.6% on-time share.
    foreach (range(1, 5) as $n) {
        creditInstallment($loan, $customer->id, $n, "2026-0{$n}-10", "2026-0{$n}-05");
    }
    foreach (range(6, 9) as $n) {
        creditInstallment($loan, $customer->id, $n, "2026-0{$n}-10", "2026-0{$n}-25");
    }

    app(CreditScoreService::class)->recomputeForCustomer($customer->id);

    $this->actingAsApi($this->userWithPermissions(['Credit Score Index', 'Credit Score Recompute']));

    $shown = $this->getJson($this->api('/credit-scores/') . $customer->id);
    $recomputed = $this->postJson($this->api('/credit-scores/') . $customer->id . '/recompute');

    $shown->assertStatus(200);
    $recomputed->assertStatus(200);

    // One customer, one record, one band. These disagreed because recompute
    // passed the point total where show passed the on-time rate.
    expect($recomputed->json('data.band'))->toBe($shown->json('data.band'));
});
