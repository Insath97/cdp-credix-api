<?php

use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Models\GroupLoan;
use App\Models\LoanInstallment;

/*
|--------------------------------------------------------------------------
| Group loans: the walk from a form to money, and the arithmetic on the way
|--------------------------------------------------------------------------
|
| A group loan moves through a coarse header lifecycle -- Available, Locked,
| Disbursed, Closed -- while the granular submitted/reviewed/verified/approved/
| accepted/active timeline lives on the single loan application underneath it.
| Review and verify therefore move the application while the header stays
| Available; approval is the first thing that moves the header.
|
| Two things are worth stating plainly because they are what makes a group loan
| different from every other loan in this system:
|
|   1. There is NO interest. The service charge is a straight percentage of the
|      approved amount, and total repayment is principal plus that charge.
|   2. The schedule is per MEMBER. One group loan of three members over twelve
|      months produces thirty-six installment rows, each carrying the id of the
|      member who owes it, so one member can fall behind without dragging the
|      other two into arrears with them.
|
| The fixture builders live in GroupLoanCreationTest.php.
|
*/

function groupLoanStatusOf(GroupLoan $groupLoan): string
{
    return $groupLoan->fresh()->status->value;
}

function groupLoanApplicationStatusOf(GroupLoan $groupLoan): string
{
    return $groupLoan->fresh()->loanApplication->status->value;
}

/**
 * A group loan already approved, with the figures approval works out.
 *
 * Built directly rather than by driving review, verify and approve again: that
 * walk is tested on its own below, and repeating it in every offer and
 * disbursement test would mean each of those could fail for a reason that has
 * nothing to do with what it is measuring.
 *
 * 180,000 at the seeded 10% service charge is 198,000 over the term.
 *
 * @return array{group: GroupLoan, application: \App\Models\LoanApplication, customers: \Illuminate\Support\Collection}
 */
function groupLoanApprovedFixture(int $members = 3, int $termMonths = 12): array
{
    $fixture = groupLoanFixture($members, $termMonths);

    $approvedAmount = 180000.00;
    $serviceCharge  = round($approvedAmount * 10 / 100, 2);
    $totalRepayment = round($approvedAmount + $serviceCharge, 2);

    $fixture['group']->update([
        'status'                 => GroupLoanStatus::Locked,
        'approved_amount'        => $approvedAmount,
        'service_charge_amount'  => $serviceCharge,
        'total_repayment_amount' => $totalRepayment,
        'amount_per_member'      => round($totalRepayment / $members, 2),
        'approved_at'            => now(),
    ]);

    $fixture['application']->update([
        'status'              => LoanApplicationStatus::Approved,
        'approved_amount'     => $approvedAmount,
        'monthly_installment' => round($totalRepayment / $termMonths, 2),
        'approved_at'         => now(),
    ]);

    return [
        'group'       => $fixture['group']->fresh(),
        'application' => $fixture['application']->fresh(),
        'customers'   => $fixture['customers'],
    ];
}

/**
 * An approved group loan whose members have accepted the offer, so the only
 * thing left is to pay the money out.
 */
function groupLoanAcceptedFixture(int $members = 3, int $termMonths = 12): array
{
    $fixture = groupLoanApprovedFixture($members, $termMonths);

    $fixture['application']->update(['status' => LoanApplicationStatus::Accepted]);

    return $fixture;
}

/*
|--------------------------------------------------------------------------
| Getting to approved
|--------------------------------------------------------------------------
*/

it('walks a group loan from submitted to locked through three separate hands', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited'])
        ->assertStatus(200);

    // Review and verification do not move the header: a submitted and a
    // verified group loan are both still "open" as far as the lifecycle tabs
    // are concerned, so only the application underneath advances.
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('reviewed');
    expect(groupLoanStatusOf($groupLoan))->toBe('available');

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Members verified'])
        ->assertStatus(200);

    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('verified');
    expect(groupLoanStatusOf($groupLoan))->toBe('available');

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 180000])
        ->assertStatus(200);

    // Approval is the first thing that moves the header, because it is the
    // first thing that freezes the money.
    expect(groupLoanStatusOf($groupLoan))->toBe('locked');
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('approved');
});

it('refuses to verify a group loan that was never reviewed', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Skipping ahead']);

    expect($response->status())->not->toBe(200);
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('submitted');
});

it('refuses to approve a group loan that was never verified', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited'])
        ->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 180000]);

    expect($response->status())->not->toBe(200);

    // The header must not have been left Locked by the half-finished cascade:
    // a group loan that is Locked but not approved can never be edited again
    // and can never be approved either.
    expect(groupLoanStatusOf($groupLoan))->toBe('available');
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('reviewed');
});

it('refuses to let the same officer both review and verify a group loan', function () {
    // Three stages exist so three people look at it; a group loan is exactly
    // where that matters most, since one officer signing off alone can commit
    // the branch to a whole village at once.
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review', 'Group Loan Verify']));

    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited'])
        ->assertStatus(200);

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Also me']);

    expect($response->status())->not->toBe(200);
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('reviewed');
});

it('refuses to review a group loan that has already been approved', function () {
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Second look']);

    expect($response->status())->not->toBe(200);
    expect(groupLoanStatusOf($groupLoan))->toBe('locked');
});

/*
|--------------------------------------------------------------------------
| The approval arithmetic
|--------------------------------------------------------------------------
*/

it('works out the service charge, the total and each share on approval', function () {
    // A group loan carries no interest at all. The whole of the arithmetic is:
    //
    //   approved amount    = 180,000.00   (10 x 12,000 + 3 x 20,000)
    //   service charge     = 180,000.00 x 10%        =  18,000.00
    //   total repayment    = 180,000.00 + 18,000.00  = 198,000.00
    //   monthly instalment = 198,000.00 / 12 months  =  16,500.00
    //   amount per member  = 198,000.00 / 3 members  =  66,000.00
    //
    // Every figure divides exactly, so anything else here is a real
    // disagreement rather than a rounding artefact.
    $groupLoan = groupLoanFixture(members: 3, termMonths: 12)['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Members verified']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 180000])
        ->assertStatus(200);

    $groupLoan->refresh();

    expect((float) $groupLoan->approved_amount)->toBe(180000.0);
    expect((float) $groupLoan->service_charge_amount)->toBe(18000.0);
    expect((float) $groupLoan->total_repayment_amount)->toBe(198000.0);
    expect((float) $groupLoan->amount_per_member)->toBe(66000.0);

    // The monthly figure lives on the application, because the group repays as
    // one loan.
    expect((float) $groupLoan->loanApplication->monthly_installment)->toBe(16500.0);

    // And no interest rate was invented along the way.
    expect($groupLoan->loanApplication->interest_rate)->toBeNull();
});

it('approves a group loan for less than it asked for', function () {
    // 90,000 at 10% is 9,000, so 99,000 over 12 months is 8,250 a month and
    // 33,000 each for three members.
    $groupLoan = groupLoanFixture(members: 3, termMonths: 12)['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Members verified']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 90000])
        ->assertStatus(200);

    $groupLoan->refresh();

    expect((float) $groupLoan->service_charge_amount)->toBe(9000.0);
    expect((float) $groupLoan->total_repayment_amount)->toBe(99000.0);
    expect((float) $groupLoan->amount_per_member)->toBe(33000.0);
    expect((float) $groupLoan->loanApplication->monthly_installment)->toBe(8250.0);
});

it('refuses to approve a group loan for more than it asked for', function () {
    // The requested amount is itself the sum of the items, so approving above
    // it would be lending money against nothing.
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Members verified']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 200000]);

    $this->assertValidationFailed($response, ['approved_amount']);
    expect(groupLoanStatusOf($groupLoan))->toBe('available');
});

/*
|--------------------------------------------------------------------------
| The group's answer to the offer
|--------------------------------------------------------------------------
*/

it('takes an accepted offer straight to disbursement', function () {
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/accept-offer', ['remarks' => 'Group accepted the offer'])
        ->assertStatus(200);

    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('accepted');

    // Accepting is the group's answer, not a lending decision, so the header
    // is still just an approved group loan.
    expect(groupLoanStatusOf($groupLoan))->toBe('locked');

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    expect(groupLoanStatusOf($groupLoan))->toBe('disbursed');

    // The header has no Active state -- "money is out and the group is
    // repaying" is one tab -- but the application underneath does.
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('active');
});

it('parks an offer on hold and then disburses it when the group accepts', function () {
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));

    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/hold-offer', ['remarks' => 'Group wants a week'])
        ->assertStatus(200);
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('on_hold');

    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/accept-offer', ['remarks' => 'Group accepted the offer'])
        ->assertStatus(200);
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('accepted');

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    expect(groupLoanStatusOf($groupLoan))->toBe('disbursed');
});

it('cancels the whole group loan when the group declines the offer', function () {
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));

    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/decline-offer', [
        'decline_reason' => 'amount_too_low',
        'remarks'        => 'Group declined the offer',
    ])->assertStatus(200);

    // A decline ends it: the group loan is settled rather than left in a state
    // nobody acts on.
    expect(groupLoanStatusOf($groupLoan))->toBe('cancelled');
    expect(groupLoanApplicationStatusOf($groupLoan))->toBe('cancelled');

    // And a finished group loan must not still read as active, because that
    // flag is what the listings and the badge go by.
    expect($groupLoan->fresh()->is_active)->toBeFalse();
});

it('refuses a decline reason that is not on the list', function () {
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/decline-offer', [
        'decline_reason' => 'they_changed_their_minds_probably',
        'remarks'        => 'Group declined the offer',
    ]);

    expect($response->status())->not->toBe(200);
    expect(groupLoanStatusOf($groupLoan))->toBe('locked');
});

it('refuses to answer an offer on a group loan that was never approved', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/accept-offer', ['remarks' => 'Getting ahead of ourselves']);

    expect($response->status())->not->toBe(200);
    expect(groupLoanStatusOf($groupLoan))->toBe('available');
});

/*
|--------------------------------------------------------------------------
| The jumps that must not be allowed
|--------------------------------------------------------------------------
*/

it('refuses to disburse an offer the group has not accepted', function () {
    // Money moves only after a yes, and no schedule is written either.
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse');

    expect($response->status())->not->toBe(200);
    expect(groupLoanStatusOf($groupLoan))->toBe('locked');
    expect(LoanInstallment::where('loan_application_id', $groupLoan->loanApplication->id)->count())->toBe(0);
});

it('refuses to disburse a group loan that was never approved', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));

    $response = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse');

    expect($response->status())->not->toBe(200);
    expect(groupLoanStatusOf($groupLoan))->toBe('available');
});

it('refuses to disburse the same group loan twice', function () {
    $groupLoan = groupLoanAcceptedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $second = $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse');

    expect($second->status())->not->toBe(200);

    // And no second schedule was written on top of the first: 3 members over
    // 12 months is 36 rows, and only 36.
    expect(LoanInstallment::where('loan_application_id', $groupLoan->loanApplication->id)->count())->toBe(36);
});

/*
|--------------------------------------------------------------------------
| Disbursement writes one schedule per member
|--------------------------------------------------------------------------
*/

it('writes one run of installments for every member', function () {
    ['group' => $groupLoan, 'customers' => $customers] = groupLoanAcceptedFixture(members: 3, termMonths: 12);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $rows = LoanInstallment::where('loan_application_id', $groupLoan->loanApplication->id)->get();

    // Three members over twelve months is thirty-six rows, not twelve: each
    // member owes their own share of every period, which is what lets one of
    // them fall behind without the other two being marked overdue.
    expect($rows->count())->toBe(36);

    foreach ($customers as $customer) {
        expect($rows->where('customer_id', $customer->id)->count())->toBe(12);
    }

    // No row belongs to nobody -- an installment with a null customer_id could
    // never be chased.
    expect($rows->whereNull('customer_id')->count())->toBe(0);
});

it('schedules exactly what the group owes and not a cent more', function () {
    // 198,000.00 total across 3 members is 66,000.00 each; at 16,500.00 a
    // month split three ways that is 5,500.00 per member per month, and
    // 12 x 5,500.00 = 66,000.00 exactly.
    ['group' => $groupLoan, 'customers' => $customers] = groupLoanAcceptedFixture(members: 3, termMonths: 12);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $rows = LoanInstallment::where('loan_application_id', $groupLoan->loanApplication->id)->get();

    expect(round((float) $rows->sum('amount_due'), 2))->toBe(198000.0);

    foreach ($customers as $customer) {
        expect(round((float) $rows->where('customer_id', $customer->id)->sum('amount_due'), 2))->toBe(66000.0);
    }

    // The loan's own outstanding figure is written from the same total, so the
    // schedule and the balance can never disagree on day one.
    expect((float) $groupLoan->loanApplication->fresh()->outstanding_balance)->toBe(198000.0);
});

it('never schedules a negative installment', function () {
    // The last installment of each member's run absorbs the rounding
    // remainder, which only works while the run is as long as the term the
    // total was struck over. If the two disagree the remainder goes the other
    // way and the final row becomes a credit -- a month in which the borrower
    // is told the lender owes them money.
    $groupLoan = groupLoanFixture(members: 3, termMonths: 12)['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Update']));
    $this->putJson($this->api('/group-loans/') . $groupLoan->id, ['term_months' => 6])->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Members verified']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 180000])
        ->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/accept-offer', ['remarks' => 'Group accepted the offer']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $rows = LoanInstallment::where('loan_application_id', $groupLoan->loanApplication->id)->get();

    expect($rows->filter(fn ($row) => (float) $row->amount_due < 0)->count())->toBe(0);
});

it('schedules the term the group was actually approved on', function () {
    // The term can legitimately be cut while the group loan is still
    // Available -- a branch reworking the repayment to fit the harvest does
    // exactly this. Approval then strikes the monthly figure over the NEW
    // term, so the schedule has to be that many months long. A schedule of a
    // different length is a repayment plan the group never agreed to.
    $groupLoan = groupLoanFixture(members: 3, termMonths: 12)['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Update']));
    $this->putJson($this->api('/group-loans/') . $groupLoan->id, ['term_months' => 6])->assertStatus(200);

    expect($groupLoan->fresh()->term_months)->toBe(6);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Review']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/review', ['remarks' => 'Group visited']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Verify']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/verify', ['remarks' => 'Members verified']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Approve']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/approve', ['approved_amount' => 180000])
        ->assertStatus(200);

    // 198,000.00 over 6 months is 33,000.00 a month, not 16,500.00.
    expect((float) $groupLoan->fresh()->loanApplication->monthly_installment)->toBe(33000.0);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Offer Response']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/accept-offer', ['remarks' => 'Group accepted the offer']);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $rows = LoanInstallment::where('loan_application_id', $groupLoan->loanApplication->id)->get();

    // Three members over six months.
    expect($rows->count())->toBe(18);
});

/*
|--------------------------------------------------------------------------
| Editing before approval, and not after
|--------------------------------------------------------------------------
*/

it('refuses to edit a group loan once it has been approved', function () {
    // By then the approved amount has been split across exactly these members
    // on exactly these terms, and rewriting the header afterwards would
    // silently disagree with the schedule generated from it.
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Update']));

    $response = $this->putJson($this->api('/group-loans/') . $groupLoan->id, ['term_months' => 6]);

    expect($response->status())->not->toBe(200);
    expect($groupLoan->fresh()->term_months)->toBe(12);
});

it('keeps the group loan and its application on the same term after an edit', function () {
    // The header and the application underneath are two rows describing one
    // loan. Approval reads the header's term while the schedule generator
    // reads the application's, so the moment the two disagree the group is
    // quoted one repayment plan and given another.
    $groupLoan = groupLoanFixture(members: 3, termMonths: 12)['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Update']));
    $this->putJson($this->api('/group-loans/') . $groupLoan->id, ['term_months' => 6])->assertStatus(200);

    $groupLoan->refresh();

    expect($groupLoan->term_months)->toBe(6);
    expect($groupLoan->loanApplication->term_months)->toBe(6);
});
