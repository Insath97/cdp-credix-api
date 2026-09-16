<?php

use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\RecoveryCase;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Recovery: opening a case automatically
|--------------------------------------------------------------------------
|
| A recovery case is meant to appear on its own once a borrower falls behind.
| Two nightly jobs decide that between them, and they read two different
| settings:
|
|   loans:mark-overdue        stamps an installment 'overdue' once the
|                             PRODUCT's grace period has elapsed
|   recovery:escalate-internal opens the case once the installment is
|                             internal_recovery_threshold_days past its DUE DATE
|
| Both counts start at the due date, but nothing links the two numbers. They
| happen to be 30 and 30 today, so a case opens the same night the installment
| turns overdue. Change either one and they drift apart, which is what these
| tests are here to make visible.
|
*/

function recoveryProduct(int $graceDays): LoanProduct
{
    $term = LoanTerm::create(['code' => 'RC-' . fake()->unique()->numerify('###'), 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'RC-' . fake()->unique()->numerify('###'), 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return LoanProduct::create([
        'name' => 'Recovery Product ' . fake()->unique()->numerify('###'),
        'code' => 'RCP-' . fake()->unique()->numerify('####'),
        'loan_type_id' => $type->id, 'loan_term_id' => $term->id,
        'interest_rate' => 10, 'interest_type' => 'flat',
        'min_amount' => 1000, 'max_amount' => 1000000,
        'min_term_months' => 1, 'max_term_months' => 60,
        'processing_fee_type' => 'fixed', 'processing_fee_value' => 0,
        'grace_period_days' => $graceDays,
        'is_active' => true,
    ]);
}

/**
 * An overdue loan with one unpaid installment, due the given number of days ago.
 */
function overdueLoan(int $dueDaysAgo, int $graceDays = 30): LoanApplication
{
    $customer = Customer::create([
        'full_name' => 'Late Payer', 'name_with_initials' => 'L. Payer',
        'id_type' => 'nic', 'id_number' => 'RC' . fake()->unique()->numerify('##########'),
        'address_line_1' => '1 Test Lane', 'country' => 'Sri Lanka',
        'date_of_birth' => '1990-01-01', 'phone_primary' => '0744125923',
        'phone_secondary' => '0744125923', 'whatsapp_number' => '0744125923',
        'have_whatsapp' => false, 'preferred_language' => 'en',
    ]);

    $application = Application::create(['application_type' => 'loan', 'requested_amount' => 120000]);

    $loan = LoanApplication::create([
        'application_id' => $application->id,
        'loan_product_id' => recoveryProduct($graceDays)->id,
        'customer_id' => $customer->id,
        'requested_amount' => 120000, 'approved_amount' => 120000,
        'interest_rate' => 10, 'term_months' => 12,
        'outstanding_balance' => 10000,
        'status' => LoanApplicationStatus::Overdue,
        'applied_at' => now()->subYear(), 'disbursed_at' => now()->subYear(),
    ]);

    LoanInstallment::create([
        'loan_application_id' => $loan->id,
        'customer_id' => $customer->id,
        'installment_no' => 1,
        'due_date' => now()->subDays($dueDaysAgo)->toDateString(),
        'amount_due' => 10000, 'amount_paid' => 0, 'penalty_amount' => 0,
        'balance' => 10000,
        'status' => 'overdue',
    ]);

    return $loan->fresh();
}

function recoverySetting(string $key, $value): void
{
    Setting::where('key', $key)->update(['value' => (string) $value]);
    Cache::forget("setting:{$key}");
}

function casesFor(LoanApplication $loan): int
{
    return RecoveryCase::where('loan_application_id', $loan->id)->count();
}

/*
|--------------------------------------------------------------------------
| The trigger
|--------------------------------------------------------------------------
*/

it('opens a recovery case once the threshold is reached', function () {
    $loan = overdueLoan(dueDaysAgo: 30);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(1);

    $case = RecoveryCase::where('loan_application_id', $loan->id)->first();
    expect($case->status)->toBe('open');
    expect($case->stage)->toBe('internal');
});

it('does not open a case before the threshold is reached', function () {
    // 29 days past due, threshold 30. Nothing yet.
    $loan = overdueLoan(dueDaysAgo: 29);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(0);
});

it('opens a case on the same night the installment turns overdue, with the seeded settings', function () {
    // This is the behaviour asked for: a case appears automatically when a
    // monthly installment goes overdue. It holds only because the product
    // grace period and the recovery threshold are both 30, and both are
    // counted from the due date. The next two tests show what happens when
    // they are not.
    $loan = overdueLoan(dueDaysAgo: 30, graceDays: 30);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(1);
});

it('leaves a loan uncovered when the product chases faster than the threshold', function () {
    // A product with a week of grace marks an installment overdue on day 7,
    // but the recovery threshold is still counted at 30 days past due. For
    // those 23 days the installment is formally overdue, the borrower is being
    // charged a late fee, and no recovery case exists for anyone to work.
    $loan = overdueLoan(dueDaysAgo: 10, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(0);
});

it('opens the case as soon as the threshold is lowered to match the product', function () {
    // The fix for the gap above is a setting, not a code change: line the
    // threshold up with the grace period and the case appears on the same day.
    recoverySetting('internal_recovery_threshold_days', 7);

    $loan = overdueLoan(dueDaysAgo: 10, graceDays: 7);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Not twice
|--------------------------------------------------------------------------
*/

it('does not open a second case while one is already live', function () {
    // The job runs every night for as long as the loan stays overdue. Without
    // this guard it would mint a fresh case each time.
    $loan = overdueLoan(dueDaysAgo: 45);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-internal')->assertSuccessful();
    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(1);
});

it('gives every case a distinct case number', function () {
    $first = overdueLoan(dueDaysAgo: 40);
    $second = overdueLoan(dueDaysAgo: 40);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    $numbers = RecoveryCase::pluck('case_no');

    expect($numbers)->toHaveCount(2);
    expect($numbers->unique())->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| The switch
|--------------------------------------------------------------------------
*/

it('opens nothing while recovery escalation is switched off', function () {
    recoverySetting('recovery_escalation_enabled', 0);

    $loan = overdueLoan(dueDaysAgo: 60);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(0);
});

it('ignores a loan that is not in the overdue status', function () {
    // The job only walks loans already stamped Overdue by loans:mark-overdue,
    // so an Active loan carrying an overdue-looking installment is not chased.
    $loan = overdueLoan(dueDaysAgo: 60);
    $loan->update(['status' => LoanApplicationStatus::Active]);

    $this->artisan('recovery:escalate-internal')->assertSuccessful();

    expect(casesFor($loan))->toBe(0);
});

/*
|--------------------------------------------------------------------------
| The grace period a product actually asked for
|--------------------------------------------------------------------------
*/

it('gives a product set to zero grace the default period instead, which is a known gap', function () {
    // Documenting current behaviour, not endorsing it.
    //
    // gracePeriodDays() resolves with `?:`, and 0 is falsy in PHP, so a product
    // set to 0 grace days inherits the 30-day installment_due_period_days
    // setting. There is consequently no way to configure a product that is
    // chased from the day a payment is missed.
    //
    // It is left this way on purpose. The column is NOT NULL DEFAULT 0, so
    // every product nobody has edited reads as 0 -- two live ones here do --
    // and making zero mean zero would move their borrowers' overdue stamp,
    // late fee and recovery case a month earlier with nobody having asked.
    // Fixing it safely means giving those products an explicit grace period
    // first, which is a configuration decision.
    //
    // If that decision is taken, change `?:` to `??` here and this test flips
    // to expecting 0.
    $product = recoveryProduct(0);

    $loan = new LoanApplication();
    $loan->setRelation('loanProduct', $product);

    expect($loan->gracePeriodDays())->toBe(30);
});

it('falls back to the setting only when the loan has no product at all', function () {
    // loan_products.grace_period_days is NOT NULL with a default of 0, so a
    // product can never be "unset" -- every one of them answers with a real
    // number. The installment_due_period_days setting therefore only ever
    // applies to a loan carrying no product, which is the single case this
    // fallback still covers.
    $loan = new LoanApplication();
    $loan->setRelation('loanProduct', null);

    expect($loan->gracePeriodDays())->toBe(30);
});
