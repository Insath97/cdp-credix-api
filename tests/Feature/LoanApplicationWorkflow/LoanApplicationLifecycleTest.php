<?php

use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\LoanApplicationGuarantor;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Loan application lifecycle
|--------------------------------------------------------------------------
|
| Three routes lead out of an approved offer, and all three are tested here end
| to end through the real endpoints:
|
|   approved -> on hold -> accepted  -> disbursed
|   approved -> on hold -> declined  -> cancelled
|   approved -> accepted -> disbursed
|   approved -> declined -> cancelled
|
| Getting to approved takes three separate hands: review, verify, approve. The
| point of the sequence is that no single person can walk a loan from a form
| into money, so the tests use a different officer at each stage and also check
| that reusing one is refused.
|
*/

function lifecycleProduct(): LoanProduct
{
    $term = LoanTerm::create(['code' => 'LC-' . fake()->unique()->numerify('####'), 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'LC-' . fake()->unique()->numerify('####'), 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return LoanProduct::create([
        'name' => 'Lifecycle Product ' . fake()->unique()->numerify('###'),
        'code' => 'LCP-' . fake()->unique()->numerify('####'),
        'loan_type_id' => $type->id, 'loan_term_id' => $term->id,
        'interest_rate' => 12, 'interest_type' => 'flat',
        'min_amount' => 1000, 'max_amount' => 1000000,
        'min_term_months' => 1, 'max_term_months' => 60,
        'processing_fee_type' => 'fixed', 'processing_fee_value' => 0,
        'is_active' => true,
    ]);
}

/**
 * A freshly submitted application, with the guarantor that verification needs.
 */
function submittedLoan(): LoanApplication
{
    $customer = Customer::create([
        'full_name' => 'Applicant', 'name_with_initials' => 'A. Applicant',
        'id_type' => 'nic', 'id_number' => 'LC' . fake()->unique()->numerify('##########'),
        'address_line_1' => '1 Test Lane', 'country' => 'Sri Lanka',
        'date_of_birth' => '1990-01-01', 'phone_primary' => '0770000000',
        'have_whatsapp' => false, 'preferred_language' => 'en',
    ]);

    $application = Application::create(['application_type' => 'loan', 'requested_amount' => 100000]);

    $loan = LoanApplication::create([
        'application_id' => $application->id,
        'loan_product_id' => lifecycleProduct()->id,
        'customer_id' => $customer->id,
        'requested_amount' => 100000,
        'interest_rate' => 12, 'interest_type' => 'flat',
        'term_months' => 12,
        'status' => LoanApplicationStatus::Submitted,
        'applied_at' => now(),
    ]);

    $guarantor = Guarantor::create([
        'customer_id' => $customer->id,
        'full_name' => 'Guarantor One',
        'type' => 'guarantor_1',
        'id_type' => 'nic',
        'id_number' => 'GR' . fake()->unique()->numerify('##########'),
    ]);

    LoanApplicationGuarantor::create([
        'loan_application_id' => $loan->id,
        'guarantor_id' => $guarantor->id,
    ]);

    return $loan->fresh();
}

/**
 * A loan already sitting at Approved, with the figures approval works out.
 *
 * Built directly rather than by driving review, verify and approve again: that
 * walk has its own test above, and repeating it in every offer test would mean
 * each one could fail for a reason that has nothing to do with the offer.
 *
 * 100,000 at 12% flat is 112,000 over 12 months.
 */
function approvedLoan(): LoanApplication
{
    $loan = submittedLoan();

    $loan->update([
        'status'              => LoanApplicationStatus::Approved,
        'approved_amount'     => 100000,
        'monthly_installment' => round(112000 / 12, 2),
        'approved_at'         => now(),
    ]);

    return $loan->fresh();
}

function statusOf(LoanApplication $loan): string
{
    return $loan->fresh()->status->value;
}

function lifecycleSetting(string $key, $value): void
{
    Setting::where('key', $key)->update(['value' => (string) $value]);
    Cache::forget("setting:{$key}");
}

/*
|--------------------------------------------------------------------------
| Getting to approved
|--------------------------------------------------------------------------
*/

it('walks a loan from submitted to approved through three separate hands', function () {
    $loan = submittedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Review']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/review', ['remarks' => 'Checked'])
        ->assertStatus(200);
    expect(statusOf($loan))->toBe('reviewed');

    $this->actingAsApi($this->userWithPermissions(['Loan Application Verify']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/verify', ['remarks' => 'Verified'])
        ->assertStatus(200);
    expect(statusOf($loan))->toBe('verified');

    $this->actingAsApi($this->userWithPermissions(['Loan Application Approve']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/approve', ['approved_amount' => 100000])
        ->assertStatus(200);
    expect(statusOf($loan))->toBe('approved');

    // Approval is where the money is worked out: 100,000 at 12% is 112,000
    // over 12 months.
    $loan->refresh();
    expect((float) $loan->approved_amount)->toBe(100000.0);
    expect((float) $loan->monthly_installment)->toBe(round(112000 / 12, 2));
});

it('refuses to verify a loan that was never reviewed', function () {
    $loan = submittedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Verify']));

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/verify', ['remarks' => 'Skipping ahead']);

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('submitted');
});

it('refuses to approve a loan that was never verified', function () {
    $loan = submittedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Review']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/review', ['remarks' => 'Checked']);

    $this->actingAsApi($this->userWithPermissions(['Loan Application Approve']));
    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/approve', ['approved_amount' => 100000]);

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('reviewed');
});

it('refuses to let the same officer both review and verify', function () {
    // The whole point of the three stages is that three people look at it.
    $loan = submittedLoan();
    $sameOfficer = $this->userWithPermissions(['Loan Application Review', 'Loan Application Verify']);

    $this->actingAsApi($sameOfficer);
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/review', ['remarks' => 'Checked'])
        ->assertStatus(200);

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/verify', ['remarks' => 'Also me']);

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('reviewed');
});

/*
|--------------------------------------------------------------------------
| Approved -> accepted -> disbursed
|--------------------------------------------------------------------------
*/

it('takes an approved offer straight to disbursement when the borrower accepts', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/accept-offer', ['remarks' => 'Borrower accepted the offer'])->assertStatus(200);
    expect(statusOf($loan))->toBe('accepted');

    $this->actingAsApi($this->userWithPermissions(['Loan Application Disburse']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/disburse')->assertStatus(200);

    // Disbursement moves it straight on to Active, because a paid-out loan is
    // a live one.
    expect(statusOf($loan))->toBe('active');

    // And the schedule exists.
    expect(LoanInstallment::where('loan_application_id', $loan->id)->count())->toBe(12);
});

/*
|--------------------------------------------------------------------------
| Approved -> on hold -> accepted / declined
|--------------------------------------------------------------------------
*/

it('parks an approved offer on hold and then disburses it when accepted', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/hold-offer', ['remarks' => 'Borrower wants a week'])
        ->assertStatus(200);
    expect(statusOf($loan))->toBe('on_hold');

    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/accept-offer', ['remarks' => 'Borrower accepted the offer'])->assertStatus(200);
    expect(statusOf($loan))->toBe('accepted');

    $this->actingAsApi($this->userWithPermissions(['Loan Application Disburse']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/disburse')->assertStatus(200);
    expect(statusOf($loan))->toBe('active');
});

it('cancels a held offer the borrower turns down', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/hold-offer', ['remarks' => 'Thinking about it'])
        ->assertStatus(200);

    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/decline-offer', [
        'decline_reason' => 'amount_too_low',
        'remarks'        => 'Borrower declined the offer',
    ])->assertStatus(200);

    // Declining settles the file: it goes on to cancelled rather than sitting
    // in a state nobody acts on.
    expect(statusOf($loan))->toBe('cancelled');
});

/*
|--------------------------------------------------------------------------
| Approved -> declined -> cancelled
|--------------------------------------------------------------------------
*/

it('cancels an approved offer the borrower turns down without holding it', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/decline-offer', [
        'decline_reason' => 'borrowed_elsewhere',
        'remarks'        => 'Borrower declined the offer',
    ])->assertStatus(200);

    expect(statusOf($loan))->toBe('cancelled');
});

it('records why the borrower declined', function () {
    // The useful question is not what one customer said, it is how many walked
    // away because the approved amount was too small, which is only countable
    // if everyone picks from the same list.
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/decline-offer', [
        'decline_reason' => 'amount_too_low',
        'remarks'        => 'Borrower declined the offer',
    ])->assertStatus(200);

    expect($loan->fresh()->offer_decline_reason)->toBe('amount_too_low');
});

it('refuses a decline reason that is not on the list', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/decline-offer', [
        'decline_reason' => 'changed_their_mind_probably',
        'remarks'        => 'Borrower declined the offer',
    ]);

    expect($response->status())->not->toBe(200);
});

/*
|--------------------------------------------------------------------------
| The transitions that must not be allowed
|--------------------------------------------------------------------------
*/

it('refuses to disburse an offer the borrower has not accepted', function () {
    // Money only ever moves after a yes.
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Disburse']));

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/disburse');

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('approved');
    expect(LoanInstallment::where('loan_application_id', $loan->id)->count())->toBe(0);
});

it('refuses to disburse the same loan twice', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/accept-offer', ['remarks' => 'Borrower accepted the offer'])->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Loan Application Disburse']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/disburse')->assertStatus(200);

    $second = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/disburse');

    expect($second->status())->not->toBe(200);

    // And no second schedule was written on top of the first.
    expect(LoanInstallment::where('loan_application_id', $loan->id)->count())->toBe(12);
});

it('refuses to accept an offer on a loan that was never approved', function () {
    $loan = submittedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/accept-offer', ['remarks' => 'Borrower accepted the offer']);

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('submitted');
});

it('refuses to put a loan on hold before it has been approved', function () {
    $loan = submittedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/hold-offer', ['remarks' => 'Too early']);

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('submitted');
});

it('refuses to reopen a cancelled application', function () {
    $loan = approvedLoan();

    $this->actingAsApi($this->userWithPermissions(['Loan Application Offer Response']));
    $this->patchJson($this->api('/loan-applications/') . $loan->id . '/decline-offer', [
        'decline_reason' => 'no_longer_needed',
        'remarks'        => 'Borrower declined the offer',
    ])->assertStatus(200);

    expect(statusOf($loan))->toBe('cancelled');

    $response = $this->patchJson($this->api('/loan-applications/') . $loan->id . '/accept-offer', ['remarks' => 'Borrower accepted the offer']);

    expect($response->status())->not->toBe(200);
    expect(statusOf($loan))->toBe('cancelled');
});
