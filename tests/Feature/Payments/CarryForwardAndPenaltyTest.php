<?php

use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\Payment;

/*
|--------------------------------------------------------------------------
| Payments: paying extra, and paying late
|--------------------------------------------------------------------------
|
| Two behaviours a borrower actually notices.
|
| Pay more than the month asks for and the surplus has to move forward onto the
| months still to come, rather than sitting on one installment as a credit
| nobody can see. Pay late and the penalty has to be part of what is owed, or
| the screen says the month is settled while the late fee is still outstanding.
|
| The balance column is the single source of truth for both:
| balance = amount_due + penalty_amount - amount_paid.
|
*/

function paymentProduct(int $penalty = 500, int $graceDays = 30): LoanProduct
{
    $term = LoanTerm::create(['code' => 'PM-' . fake()->unique()->numerify('####'), 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'PM-' . fake()->unique()->numerify('####'), 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return LoanProduct::create([
        'name' => 'Payment Product ' . fake()->unique()->numerify('###'),
        'code' => 'PMP-' . fake()->unique()->numerify('####'),
        'loan_type_id' => $type->id, 'loan_term_id' => $term->id,
        'interest_rate' => 10, 'interest_type' => 'flat',
        'min_amount' => 1000, 'max_amount' => 1000000,
        'min_term_months' => 1, 'max_term_months' => 60,
        'processing_fee_type' => 'fixed', 'processing_fee_value' => 0,
        'penalty_type' => 'fixed', 'penalty_value' => $penalty,
        'grace_period_days' => $graceDays,
        'is_active' => true,
    ]);
}

/**
 * A live loan with three months of 10,000 still to run, none of them paid.
 *
 * @return array{loan: LoanApplication, installments: \Illuminate\Support\Collection}
 */
function payableLoan(int $months = 3, int $penalty = 500, int $graceDays = 30): array
{
    $customer = Customer::create([
        'full_name' => 'Paying Borrower', 'name_with_initials' => 'P. Borrower',
        'id_type' => 'nic', 'id_number' => 'PM' . fake()->unique()->numerify('##########'),
        'address_line_1' => '1 Test Lane', 'country' => 'Sri Lanka',
        'date_of_birth' => '1990-01-01', 'phone_primary' => '0744125923',
        'phone_secondary' => '0744125923', 'whatsapp_number' => '0744125923',
        'have_whatsapp' => false, 'preferred_language' => 'en',
    ]);

    $application = Application::create(['application_type' => 'loan', 'requested_amount' => 30000]);

    $loan = LoanApplication::create([
        'application_id' => $application->id,
        'loan_product_id' => paymentProduct($penalty, $graceDays)->id,
        'customer_id' => $customer->id,
        'requested_amount' => 30000, 'approved_amount' => 30000,
        'interest_rate' => 10, 'term_months' => $months,
        'monthly_installment' => 10000,
        'outstanding_balance' => 10000 * $months,
        'status' => LoanApplicationStatus::Active,
        'applied_at' => now()->subMonths($months), 'disbursed_at' => now()->subMonths($months),
    ]);

    foreach (range(1, $months) as $n) {
        LoanInstallment::create([
            'loan_application_id' => $loan->id,
            'customer_id' => $customer->id,
            'installment_no' => $n,
            'due_date' => now()->addMonths($n)->toDateString(),
            'amount_due' => 10000, 'amount_paid' => 0, 'penalty_amount' => 0,
            'balance' => 10000,
            'status' => 'upcoming',
        ]);
    }

    return [
        'loan' => $loan->fresh(),
        'installments' => LoanInstallment::where('loan_application_id', $loan->id)->orderBy('installment_no')->get(),
    ];
}

function payTowards(LoanApplication $loan, LoanInstallment $installment, float $amount): Payment
{
    return Payment::create([
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $installment->id,
        'customer_id' => $loan->customer_id,
        'amount' => $amount,
        'payment_method' => 'cash',
        'paid_at' => now(),
        'receipt_no' => 'RCP-' . fake()->unique()->numerify('######'),
    ]);
}

/*
|--------------------------------------------------------------------------
| Paying exactly, and paying short
|--------------------------------------------------------------------------
*/

it('settles a month paid in full', function () {
    ['loan' => $loan, 'installments' => $rows] = payableLoan();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 10000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    $first = $rows[0]->fresh();

    expect($first->status)->toBe('paid');
    expect((float) $first->balance)->toBe(0.0);
    expect((float) $loan->fresh()->outstanding_balance)->toBe(20000.0);
});

it('leaves a part payment owing the rest of the month', function () {
    ['loan' => $loan, 'installments' => $rows] = payableLoan();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 4000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    $first = $rows[0]->fresh();

    expect($first->status)->toBe('partially_paid');
    expect((float) $first->balance)->toBe(6000.0);
});

/*
|--------------------------------------------------------------------------
| Paying extra moves forward
|--------------------------------------------------------------------------
*/

it('carries a surplus onto next month', function () {
    // 15,000 against a 10,000 month: the month is settled and 5,000 lands on
    // the next one, rather than sitting as an invisible credit.
    ['loan' => $loan, 'installments' => $rows] = payableLoan();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 15000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    expect($rows[0]->fresh()->status)->toBe('paid');

    $second = $rows[1]->fresh();
    expect((float) $second->amount_paid)->toBe(5000.0);
    expect((float) $second->balance)->toBe(5000.0);
    expect($second->status)->toBe('partially_paid');

    // And the third month is untouched.
    expect((float) $rows[2]->fresh()->balance)->toBe(10000.0);
});

it('carries a surplus across several months at once', function () {
    // 25,000 clears months one and two outright and leaves 5,000 on the third.
    ['loan' => $loan, 'installments' => $rows] = payableLoan();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 25000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    expect($rows[0]->fresh()->status)->toBe('paid');
    expect($rows[1]->fresh()->status)->toBe('paid');
    expect((float) $rows[2]->fresh()->balance)->toBe(5000.0);

    expect((float) $loan->fresh()->outstanding_balance)->toBe(5000.0);
});

it('records what was carried forward on the receipt', function () {
    // The breakdown is what makes the surplus auditable, and it is what a
    // reversal reads to put every month back.
    ['loan' => $loan, 'installments' => $rows] = payableLoan();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 25000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    $breakdown = Payment::latest('id')->firstOrFail()->carry_forward_breakdown;

    expect($breakdown)->toBeArray();
    expect((float) collect($breakdown)->sum('amount'))->toBe(15000.0);
});

it('reports money that could not be placed anywhere', function () {
    // 40,000 against a 30,000 loan: everything is settled and 10,000 has
    // nowhere to go. It must be reported rather than silently absorbed.
    ['loan' => $loan, 'installments' => $rows] = payableLoan();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $response = $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 40000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ]);

    $response->assertStatus(201);

    // Reported at the top level of the response, beside data, because it is a
    // fact about the transaction rather than a column on the payment row.
    expect((float) $response->json('unapplied_excess'))->toBe(10000.0);
    expect((float) $loan->fresh()->outstanding_balance)->toBe(0.0);
});

/*
|--------------------------------------------------------------------------
| Paying late: the penalty counts
|--------------------------------------------------------------------------
*/

it('adds the late fee to what the month owes', function () {
    // An installment 40 days past a 30-day grace period earns the product's
    // 500 penalty, and the balance has to carry it: 10,000 + 500.
    ['loan' => $loan, 'installments' => $rows] = payableLoan(penalty: 500, graceDays: 30);

    $rows[0]->update(['due_date' => now()->subDays(40)->toDateString(), 'status' => 'due']);
    $loan->update(['status' => LoanApplicationStatus::Active]);

    $this->artisan('loans:mark-overdue')->assertSuccessful();

    $first = $rows[0]->fresh();

    expect((float) $first->penalty_amount)->toBe(500.0);
    expect((float) $first->balance)->toBe(10500.0);
    expect($first->status)->toBe('overdue');

    // The loan's own figure moves with it, so the two never disagree.
    expect((float) $loan->fresh()->outstanding_balance)->toBe(30500.0);
});

it('does not settle a late month until the penalty is paid too', function () {
    // Paying the installment but not the late fee leaves 500 owing, so the
    // month is not closed.
    ['loan' => $loan, 'installments' => $rows] = payableLoan(penalty: 500, graceDays: 30);

    $rows[0]->update(['due_date' => now()->subDays(40)->toDateString(), 'status' => 'due']);
    $this->artisan('loans:mark-overdue')->assertSuccessful();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 10000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    $first = $rows[0]->fresh();

    expect((float) $first->balance)->toBe(500.0);
    expect($first->status)->toBe('partially_paid');
});

it('settles a late month once the fee is paid with it', function () {
    ['loan' => $loan, 'installments' => $rows] = payableLoan(penalty: 500, graceDays: 30);

    $rows[0]->update(['due_date' => now()->subDays(40)->toDateString(), 'status' => 'due']);
    $this->artisan('loans:mark-overdue')->assertSuccessful();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 10500,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    expect($rows[0]->fresh()->status)->toBe('paid');
    expect((float) $rows[0]->fresh()->balance)->toBe(0.0);
});

it('carries the surplus forward only after the late fee is covered', function () {
    // 12,000 against a month owing 10,500: the fee and the installment are both
    // cleared and only the remaining 1,500 moves on.
    ['loan' => $loan, 'installments' => $rows] = payableLoan(penalty: 500, graceDays: 30);

    $rows[0]->update(['due_date' => now()->subDays(40)->toDateString(), 'status' => 'due']);
    $this->artisan('loans:mark-overdue')->assertSuccessful();

    $this->actingAsApi($this->userWithPermissions(['Payment Create']));

    $this->postJson($this->api('/payments'), [
        'loan_application_id' => $loan->id,
        'loan_installment_id' => $rows[0]->id,
        'amount' => 12000,
        'payment_method' => 'cash',
        'paid_at' => now()->toDateTimeString(),
    ])->assertStatus(201);

    expect($rows[0]->fresh()->status)->toBe('paid');
    expect((float) $rows[1]->fresh()->amount_paid)->toBe(1500.0);
});

it('does not charge the same late fee twice on later runs', function () {
    ['loan' => $loan, 'installments' => $rows] = payableLoan(penalty: 500, graceDays: 30);

    $rows[0]->update(['due_date' => now()->subDays(40)->toDateString(), 'status' => 'due']);

    $this->artisan('loans:mark-overdue')->assertSuccessful();
    $this->artisan('loans:mark-overdue')->assertSuccessful();
    $this->artisan('loans:mark-overdue')->assertSuccessful();

    expect((float) $rows[0]->fresh()->penalty_amount)->toBe(500.0);
});

/*
|--------------------------------------------------------------------------
| The installment listing shows both
|--------------------------------------------------------------------------
*/

it('shows the penalty and the balance that includes it', function () {
    ['loan' => $loan, 'installments' => $rows] = payableLoan(penalty: 500, graceDays: 30);

    $rows[0]->update(['due_date' => now()->subDays(40)->toDateString(), 'status' => 'due']);
    $this->artisan('loans:mark-overdue')->assertSuccessful();

    $this->actingAsApi($this->userWithPermissions(['Loan Installment Index']));

    $response = $this->getJson($this->api('/loan-installments?loan_application_id=') . $loan->id);

    $response->assertStatus(200);

    $row = collect($response->json('data.data'))->firstWhere('id', $rows[0]->id);

    expect((float) $row['penalty_amount'])->toBe(500.0);
    expect((float) $row['balance'])->toBe(10500.0);
});
