<?php

use App\Models\GroupLoan;
use App\Models\LoanApplication;

/*
|--------------------------------------------------------------------------
| Group loans: deleting one
|--------------------------------------------------------------------------
|
| Deletion is only ever safe while the group loan is still a proposal. Once the
| money is out there is a loan application, a per-member schedule and payments
| hanging off the group, and the group_loans row is what ties them together --
| so removing it does not undo the lending, it only hides the thing that
| explains it.
|
| The fixture builders live in GroupLoanCreationTest.php.
|
*/

it('deletes a group loan that has not been approved yet', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Delete']));

    $this->deleteJson($this->api('/group-loans/') . $groupLoan->id)->assertStatus(200);

    expect(GroupLoan::find($groupLoan->id))->toBeNull();

    // Soft-deleted, not erased: the number it was issued must never be handed
    // to another group loan.
    expect(GroupLoan::withTrashed()->find($groupLoan->id))->not->toBeNull();
});

it('drops a deleted group loan out of the listing and the badges', function () {
    $groupLoan = groupLoanFixture()['group'];
    groupLoanFixture();

    $this->actingAsApi($this->userWithPermissions(['Group Loan Delete']));
    $this->deleteJson($this->api('/group-loans/') . $groupLoan->id)->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    expect($this->getJson($this->api('/group-loans'))->json('data.data'))->toHaveCount(1);
    expect($this->getJson($this->api('/group-loans/status-counts'))->json('data.available'))->toBe(1);
});

it('refuses to delete a disbursed group loan', function () {
    // The money has gone out. The group's application, its per-member schedule
    // and every payment against it survive the delete and go on being read by
    // the collections screens, the overdue job and the recovery module -- all
    // of which reach back through group_loan_id to a row that is no longer
    // there. Whatever the reason for wanting it gone, it is not deletion:
    // there is a cancel endpoint for a group loan that should not have been,
    // and it leaves the trail intact.
    $groupLoan = groupLoanAcceptedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Delete']));

    $response = $this->deleteJson($this->api('/group-loans/') . $groupLoan->id);

    expect($response->status())->not->toBe(200);
    expect(GroupLoan::find($groupLoan->id))->not->toBeNull();
});

it('does not leave a live loan application pointing at a missing group', function () {
    // The application is the row that carries the money, and it survives the
    // delete either way. What must not survive is the state where it is still
    // live and its group is gone.
    $groupLoan = groupLoanAcceptedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Disburse']));
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/disburse')->assertStatus(200);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Delete']));
    $this->deleteJson($this->api('/group-loans/') . $groupLoan->id);

    $application = LoanApplication::where('group_loan_id', $groupLoan->id)->first();

    expect($application)->not->toBeNull();
    expect(GroupLoan::find($application->group_loan_id))->not->toBeNull();
});

it('answers 404 when deleting a group loan that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Delete']));

    $this->deleteJson($this->api('/group-loans/999999'))->assertStatus(404);
});
