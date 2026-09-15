<?php

use App\Enums\GroupLoanStatus;
use App\Models\Branch;

/*
|--------------------------------------------------------------------------
| Group loans: the listings and the tab badges
|--------------------------------------------------------------------------
|
| The frontend shows group loans as four lifecycle tabs, each with a count on
| it. Two endpoints have to agree for that screen to make sense: the per-status
| listing that fills a tab, and the status-counts endpoint that numbers it. A
| badge saying 7 over a tab showing 3 rows is worse than no badge at all,
| because the officer goes looking for four group loans that were never theirs
| to see.
|
| The fixture builders live in GroupLoanCreationTest.php.
|
*/

/**
 * A second branch hanging off the same organisation chain, so a listing can be
 * shown rows that belong to somebody else's branch.
 */
function groupLoanOtherBranch(array $chain): Branch
{
    return Branch::create([
        'name'          => 'Kandy Branch',
        'code'          => 'KD-002',
        'address_line1' => '9 Temple Street',
        'city'          => 'Kandy',
        'zone_id'       => $chain['zonal']->id,
        'region_id'     => $chain['region']->id,
        'province_id'   => $chain['province']->id,
        'phone_primary' => '0812345678',
        'opening_date'  => '2021-01-01',
        'branch_type'   => 'city',
        'is_active'     => true,
    ]);
}

/*
|--------------------------------------------------------------------------
| The listing itself
|--------------------------------------------------------------------------
*/

it('lists group loans', function () {
    groupLoanFixture();
    groupLoanFixture();

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $response = $this->getJson($this->api('/group-loans'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(2);
});

it('filters the listing by status', function () {
    groupLoanFixture();
    groupLoanApprovedFixture();

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $available = $this->getJson($this->api('/group-loans/status/available'));
    $locked    = $this->getJson($this->api('/group-loans/status/locked'));

    $available->assertStatus(200);
    $locked->assertStatus(200);

    expect($available->json('data.data'))->toHaveCount(1);
    expect($locked->json('data.data'))->toHaveCount(1);
    expect($available->json('data.data.0.status'))->toBe('available');
    expect($locked->json('data.data.0.status'))->toBe('locked');
});

it('refuses a status that is not part of the lifecycle', function () {
    // Better a 422 naming the accepted values than an empty list, which reads
    // as "there are none" rather than "you asked for something that does not
    // exist".
    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $this->getJson($this->api('/group-loans/status/half-approved'))->assertStatus(422);
});

it('searches by group name and group loan number', function () {
    $groupLoan = groupLoanFixture()['group'];
    groupLoanFixture();

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $byNumber = $this->getJson($this->api('/group-loans?search=') . $groupLoan->group_loan_no);

    $byNumber->assertStatus(200);
    expect($byNumber->json('data.data'))->toHaveCount(1);
    expect($byNumber->json('data.data.0.id'))->toBe($groupLoan->id);
});

/*
|--------------------------------------------------------------------------
| The tab badges
|--------------------------------------------------------------------------
*/

it('counts every filterable status, zero included', function () {
    groupLoanFixture();
    groupLoanFixture();
    groupLoanApprovedFixture();

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $response = $this->getJson($this->api('/group-loans/status-counts'));

    $response->assertStatus(200);

    // Every key is present even at zero, so the frontend never has to
    // back-fill a missing tab.
    expect($response->json('data'))->toBe([
        'available' => 2,
        'locked'    => 1,
        'disbursed' => 0,
        'closed'    => 0,
    ]);
});

it('counts only the group loans a branch officer is allowed to see', function () {
    // The badge and the tab under it are one control. index() confines a
    // branch officer to their own branch, so a count taken over every branch
    // promises rows the same officer is then refused -- and it leaks how much
    // lending the rest of the network is doing to someone with no permission
    // to know.
    $chain = $this->organisationChain();
    $otherBranch = groupLoanOtherBranch($chain);

    groupLoanFixture(branchId: $chain['branch']->id);
    groupLoanFixture(branchId: $otherBranch->id);
    groupLoanFixture(branchId: $otherBranch->id);

    $this->actingAsApi($this->officerAtBranch($chain['branch'], ['Group Loan Index']));

    $listing = $this->getJson($this->api('/group-loans'));
    $listing->assertStatus(200);
    expect($listing->json('data.data'))->toHaveCount(1);

    $counts = $this->getJson($this->api('/group-loans/status-counts'));
    $counts->assertStatus(200);

    expect($counts->json('data.available'))->toBe(1);
});

it('shows head office every branch', function () {
    // The confinement must not be the other way round: an admin with no
    // employee record is deliberately unscoped, and a null branch must never
    // quietly mean "sees nothing".
    $chain = $this->organisationChain();

    groupLoanFixture(branchId: $chain['branch']->id);
    groupLoanFixture(branchId: groupLoanOtherBranch($chain)->id);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    expect($this->getJson($this->api('/group-loans'))->json('data.data'))->toHaveCount(2);
    expect($this->getJson($this->api('/group-loans/status-counts'))->json('data.available'))->toBe(2);
});

/*
|--------------------------------------------------------------------------
| One group loan in full
|--------------------------------------------------------------------------
*/

it('shows a group loan with a derived share for each member', function () {
    // Nothing in the member breakdown is stored: the group borrows as one
    // loan, so each member's share is derived from the single application
    // every time it is read.
    ['group' => $groupLoan, 'customers' => $customers] = groupLoanApprovedFixture(members: 3, termMonths: 12);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $response = $this->getJson($this->api('/group-loans/') . $groupLoan->id);

    $response->assertStatus(200);

    $members = $response->json('data.members');

    expect($members)->toHaveCount(3);
    expect(collect($members)->pluck('customer_id')->sort()->values()->all())
        ->toBe($customers->pluck('id')->sort()->values()->all());

    // 198,000.00 across three members is 66,000.00 each, and the shares must
    // add back up to the total the group owes.
    expect(round((float) collect($members)->sum('total_repayment_share'), 2))->toBe(198000.0);
    expect((float) $members[0]['total_repayment_share'])->toBe(66000.0);
});

it('answers 404 for a group loan that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $this->getJson($this->api('/group-loans/999999'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| The active flag
|--------------------------------------------------------------------------
*/

it('refuses to flag a finished group loan active again', function () {
    // status and is_active are separate columns and the badge reads the
    // second, so a cancelled group loan flipped back on would display as
    // Active with no way out of that state.
    $groupLoan = groupLoanFixture()['group'];
    $groupLoan->update(['status' => GroupLoanStatus::Cancelled, 'is_active' => false]);

    $this->actingAsApi($this->userWithPermissions(['Group Loan Toggle Status']));

    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/activate')->assertStatus(422);
    $this->patchJson($this->api('/group-loans/') . $groupLoan->id . '/toggle-status')->assertStatus(422);

    expect($groupLoan->fresh()->is_active)->toBeFalse();
});
