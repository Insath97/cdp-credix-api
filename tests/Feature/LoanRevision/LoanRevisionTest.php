<?php

use App\Enums\LoanApplicationStatus;
use App\Enums\LoanRevisionStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanRevision;
use App\Models\LoanTerm;
use App\Models\LoanType;
use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| Loan revisions
|--------------------------------------------------------------------------
|
| A hardship route for Islamic finance loans only. A borrower who has fallen
| behind submits proof that they cannot pay, an officer raises a revision, and
| once it is approved the remaining schedule is rebuilt.
|
| Three shapes, and each is driven by a different input:
|
|   principal_only      the profit is written off, so only the capital is left
|                       to repay across the months already remaining
|   extend_term         the term grows, the debt does not, so each month costs
|                       less
|   reduce_installment  the officer names a monthly figure the borrower can
|                       manage and the term stretches to cover the same debt
|
| The arithmetic below is written out in full, because a revision changes what
| a real borrower owes.
|
*/

function islamicProduct(bool $islamic = true): LoanProduct
{
    $term = LoanTerm::create(['code' => 'LR-' . fake()->unique()->numerify('####'), 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'LR-' . fake()->unique()->numerify('####'), 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return LoanProduct::create([
        'name' => 'Revision Product ' . fake()->unique()->numerify('###'),
        'code' => 'LRP-' . fake()->unique()->numerify('####'),
        'loan_type_id' => $type->id, 'loan_term_id' => $term->id,
        'interest_rate' => 10, 'interest_type' => 'flat',
        'min_amount' => 1000, 'max_amount' => 1000000,
        'min_term_months' => 1, 'max_term_months' => 60,
        'processing_fee_type' => 'fixed', 'processing_fee_value' => 0,
        'is_islamic' => $islamic,
        'is_active' => true,
    ]);
}

/**
 * A live Islamic loan of 100,000 at 10%, so 110,000 payable over 12 months.
 *
 * Every installment is left unpaid, which is the state a borrower in hardship
 * is actually in when they ask for a revision.
 */
function revisableLoan(bool $islamic = true, int $months = 12): LoanApplication
{
    $customer = Customer::create([
        'full_name' => 'Hardship Borrower', 'name_with_initials' => 'H. Borrower',
        'id_type' => 'nic', 'id_number' => 'LR' . fake()->unique()->numerify('##########'),
        'address_line_1' => '1 Test Lane', 'country' => 'Sri Lanka',
        'date_of_birth' => '1990-01-01', 'phone_primary' => '0770000000',
        'have_whatsapp' => false, 'preferred_language' => 'en',
    ]);

    $application = Application::create(['application_type' => 'loan', 'requested_amount' => 100000]);

    // 100,000 capital + 10,000 profit = 110,000 over 12 months.
    $monthly = round(110000 / $months, 2);

    $loan = LoanApplication::create([
        'application_id' => $application->id,
        'loan_product_id' => islamicProduct($islamic)->id,
        'customer_id' => $customer->id,
        'requested_amount' => 100000, 'approved_amount' => 100000,
        'interest_rate' => 10, 'interest_type' => 'flat',
        'term_months' => $months,
        'monthly_installment' => $monthly,
        'outstanding_balance' => 110000,
        'status' => LoanApplicationStatus::Overdue,
        'applied_at' => now()->subYear(), 'disbursed_at' => now()->subYear(),
    ]);

    foreach (range(1, $months) as $n) {
        LoanInstallment::create([
            'loan_application_id' => $loan->id,
            'customer_id' => $customer->id,
            'installment_no' => $n,
            'due_date' => now()->subMonths($months - $n + 1)->toDateString(),
            'amount_due' => $n === $months ? round(110000 - ($monthly * ($months - 1)), 2) : $monthly,
            'amount_paid' => 0, 'penalty_amount' => 0,
            'balance' => $n === $months ? round(110000 - ($monthly * ($months - 1)), 2) : $monthly,
            'status' => 'overdue',
        ]);
    }

    return $loan->fresh();
}

function proofDocument(): UploadedFile
{
    return UploadedFile::fake()->create('hardship-proof.pdf', 40, 'application/pdf');
}

/*
|--------------------------------------------------------------------------
| Who may be revised
|--------------------------------------------------------------------------
*/

it('refuses a revision on a loan that is not Islamic', function () {
    // The whole mechanism exists because an Islamic product charges a profit
    // that can be waived. A conventional loan has interest that cannot.
    $loan = revisableLoan(islamic: false);

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $response = $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'Lost employment.',
        'document'            => proofDocument(),
    ]);

    expect($response->status())->not->toBe(201);
    expect(LoanRevision::count())->toBe(0);
});

it('refuses a revision without proof of hardship', function () {
    // The document is what the approver signs off against.
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->assertValidationFailed(
        $this->post($this->api('/loan-revisions'), [
            'loan_application_id' => $loan->id,
            'revision_type'       => 'principal_only',
            'reason'              => 'Lost employment.',
        ]),
        ['document']
    );
});

it('refuses a revision without a stated reason', function () {
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->assertValidationFailed(
        $this->post($this->api('/loan-revisions'), [
            'loan_application_id' => $loan->id,
            'revision_type'       => 'principal_only',
            'document'            => proofDocument(),
        ]),
        ['reason']
    );
});

it('refuses a second revision once one has been approved', function () {
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create', 'Loan Revision Approve']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'Lost employment.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $revision = LoanRevision::firstOrFail();
    $this->patchJson($this->api('/loan-revisions/') . $revision->id . '/approve')->assertStatus(200);

    $second = $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'extend_term',
        'revised_term'        => 24,
        'reason'              => 'Still struggling.',
        'document'            => proofDocument(),
    ]);

    expect($second->status())->not->toBe(201);
    expect(LoanRevision::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Principal only
|--------------------------------------------------------------------------
*/

it('writes off the profit and leaves only the capital to repay', function () {
    // 100,000 capital and 10,000 profit, so 110,000 payable over 12 months,
    // none of it paid. The capital's share of the schedule is
    // 100,000 / 110,000, and applying that to the 110,000 still outstanding
    // leaves exactly the 100,000 capital.
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'Lost employment.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $revision = LoanRevision::firstOrFail();

    expect((float) $revision->revised_outstanding_amount)->toBe(100000.0);

    // The months already there are kept; each one simply costs less.
    expect($revision->revised_term)->toBe(12);
    expect((float) $revision->revised_installment_amount)->toBe(round(100000 / 12, 2));
});

it('rebuilds the schedule to the capital only once approved', function () {
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create', 'Loan Revision Approve']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'Lost employment.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $revision = LoanRevision::firstOrFail();

    $this->patchJson($this->api('/loan-revisions/') . $revision->id . '/approve')->assertStatus(200);

    expect($revision->fresh()->status)->toBe(LoanRevisionStatus::Approved);

    // The borrower now owes the capital and nothing else.
    $live = LoanInstallment::where('loan_application_id', $loan->id)
        ->where('status', '!=', 'revised')
        ->get();

    expect(round((float) $live->sum('amount_due'), 2))->toBe(100000.0);
    expect((float) $loan->fresh()->outstanding_balance)->toBe(100000.0);
});

/*
|--------------------------------------------------------------------------
| Extend term
|--------------------------------------------------------------------------
*/

it('stretches the term and lowers the monthly amount without changing the debt', function () {
    // 110,000 over 24 months instead of 12.
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'extend_term',
        'revised_term'        => 24,
        'reason'              => 'Reduced income.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $revision = LoanRevision::firstOrFail();

    expect($revision->revised_term)->toBe(24);
    expect((float) $revision->revised_outstanding_amount)->toBe(110000.0);
    expect((float) $revision->revised_installment_amount)->toBe(round(110000 / 24, 2));
});

it('refuses a term that is not longer than the one remaining', function () {
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $response = $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'extend_term',
        'revised_term'        => 12,
        'reason'              => 'Reduced income.',
        'document'            => proofDocument(),
    ]);

    expect($response->status())->not->toBe(201);
});

/*
|--------------------------------------------------------------------------
| Reduce installment
|--------------------------------------------------------------------------
*/

it('lowers the monthly amount and stretches the term to cover the same debt', function () {
    // 110,000 at 5,000 a month needs 22 months.
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'reduce_installment',
        'revised_installment_amount' => 5000,
        'reason'              => 'Reduced income.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $revision = LoanRevision::firstOrFail();

    expect((float) $revision->revised_installment_amount)->toBe(5000.0);
    expect((float) $revision->revised_outstanding_amount)->toBe(110000.0);
    expect($revision->revised_term)->toBe(22);
});

it('refuses a revised installment that is not lower than the current one', function () {
    // The current month is 9,166.67. Asking for more is not hardship relief.
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $response = $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'reduce_installment',
        'revised_installment_amount' => 9500,
        'reason'              => 'Reduced income.',
        'document'            => proofDocument(),
    ]);

    expect($response->status())->not->toBe(201);
});

it('rebuilds the schedule to the reduced monthly amount once approved', function () {
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create', 'Loan Revision Approve']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'reduce_installment',
        'revised_installment_amount' => 5000,
        'reason'              => 'Reduced income.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $this->patchJson($this->api('/loan-revisions/') . LoanRevision::firstOrFail()->id . '/approve')
        ->assertStatus(200);

    $live = LoanInstallment::where('loan_application_id', $loan->id)
        ->where('status', '!=', 'revised')
        ->orderBy('installment_no')
        ->get();

    expect($live)->toHaveCount(22);

    // Every month but the last is the agreed figure, and the debt still adds up.
    expect((float) $live->first()->amount_due)->toBe(5000.0);
    expect(round((float) $live->sum('amount_due'), 2))->toBe(110000.0);
});

/*
|--------------------------------------------------------------------------
| Approval
|--------------------------------------------------------------------------
*/

it('leaves the schedule alone while the revision is still pending', function () {
    // Raising a revision is a request, not a change. Nothing moves until an
    // approver signs it off.
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'Lost employment.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    expect((float) $loan->fresh()->outstanding_balance)->toBe(110000.0);
    expect(LoanRevision::firstOrFail()->status)->toBe(LoanRevisionStatus::PendingApproval);
});

it('needs the approve permission, not merely the create one', function () {
    $loan = revisableLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'Lost employment.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    $this->patchJson($this->api('/loan-revisions/') . LoanRevision::firstOrFail()->id . '/approve')
        ->assertStatus(403);
});

it('allows a revision on a loan that is not behind at all', function () {
    // Documenting current behaviour, which is wider than the hardship route it
    // is described as. Nothing checks that the borrower has actually missed a
    // payment: an Active loan with every installment upcoming can have its
    // profit written off in full. The only status rule is Active or Overdue.
    $loan = revisableLoan();
    $loan->update(['status' => \App\Enums\LoanApplicationStatus::Active]);
    \App\Models\LoanInstallment::where('loan_application_id', $loan->id)
        ->update(['status' => 'upcoming', 'due_date' => now()->addMonth()->toDateString()]);

    $this->actingAsApi($this->userWithPermissions(['Loan Revision Create']));

    $this->post($this->api('/loan-revisions'), [
        'loan_application_id' => $loan->id,
        'revision_type'       => 'principal_only',
        'reason'              => 'No hardship stated.',
        'document'            => proofDocument(),
    ])->assertStatus(201);

    expect((float) \App\Models\LoanRevision::firstOrFail()->revised_outstanding_amount)->toBe(100000.0);
});
