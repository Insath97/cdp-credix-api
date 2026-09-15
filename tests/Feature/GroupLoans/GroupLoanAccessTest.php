<?php

/*
|--------------------------------------------------------------------------
| Group loans: who may do what
|--------------------------------------------------------------------------
|
| Every group loan route sits behind the JWT guard and behind one named
| permission, and the two failures look nothing alike: no token at all is a
| 401, while a signed-in user who simply lacks the permission is a 403. Both
| are checked for every verb, because a route added to the resource without a
| matching entry in GroupLoanController::middleware() would be wide open to
| anyone holding any permission at all, and nothing else in the suite would
| notice.
|
| The fixture builders used here live in GroupLoanCreationTest.php -- Pest
| loads the whole directory into one scope.
|
*/

it('refuses every group loan route to a request with no token', function () {
    $this->getJson($this->api('/group-loans'))->assertStatus(401);
    $this->getJson($this->api('/group-loans/1'))->assertStatus(401);
    $this->getJson($this->api('/group-loans/status-counts'))->assertStatus(401);
    $this->getJson($this->api('/group-loans/status/available'))->assertStatus(401);
    $this->postJson($this->api('/group-loans'), [])->assertStatus(401);
    $this->putJson($this->api('/group-loans/1'), [])->assertStatus(401);
    $this->deleteJson($this->api('/group-loans/1'))->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/review'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/verify'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/approve'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/reject'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/hold-offer'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/accept-offer'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/decline-offer'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/disburse'))->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/cancel'), [])->assertStatus(401);
    $this->patchJson($this->api('/group-loans/1/toggle-status'))->assertStatus(401);
});

it('refuses every group loan route to a signed-in user holding no permissions', function () {
    // A real group loan, so a 403 cannot be a 404 in disguise: the permission
    // middleware runs before the controller ever looks the row up, and a test
    // pointed at a missing id would pass even if the middleware were removed.
    $groupLoan = groupLoanFixture()['group'];
    $id = $groupLoan->id;

    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/group-loans'))->assertStatus(403);
    $this->getJson($this->api('/group-loans/') . $id)->assertStatus(403);
    $this->getJson($this->api('/group-loans/status-counts'))->assertStatus(403);
    $this->getJson($this->api('/group-loans/status/available'))->assertStatus(403);
    $this->postJson($this->api('/group-loans'), [])->assertStatus(403);
    $this->putJson($this->api('/group-loans/') . $id, [])->assertStatus(403);
    $this->deleteJson($this->api('/group-loans/') . $id)->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/review', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/verify', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/approve', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/reject', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/hold-offer', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/accept-offer', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/decline-offer', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/disburse')->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/cancel', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/toggle-status')->assertStatus(403);
});

it('does not let one group loan permission stand in for another', function () {
    // The dangerous shape of this bug is a route that was added to the file
    // but not to the middleware map, which would let a read-only officer
    // approve and disburse. Holding only the listing permission must therefore
    // buy exactly the listings and nothing else.
    $groupLoan = groupLoanFixture()['group'];
    $id = $groupLoan->id;

    $this->actingAsApi($this->userWithPermissions(['Group Loan Index']));

    $this->getJson($this->api('/group-loans'))->assertStatus(200);

    $this->postJson($this->api('/group-loans'), [])->assertStatus(403);
    $this->putJson($this->api('/group-loans/') . $id, [])->assertStatus(403);
    $this->deleteJson($this->api('/group-loans/') . $id)->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/approve', [])->assertStatus(403);
    $this->patchJson($this->api('/group-loans/') . $id . '/disburse')->assertStatus(403);
});

it('refuses the back-office group loan list to a customer principal', function () {
    // Customer tokens belong to the portal. Group loan members can see their
    // own loan there; the back-office listing is a different audience.
    $this->actingAsApi($this->customerUser());

    $this->getJson($this->api('/group-loans'))->assertStatus(403);
});
