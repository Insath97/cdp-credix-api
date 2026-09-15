<?php

use App\Models\Designation;

/*
|--------------------------------------------------------------------------
| Organisation setup: Designations
|--------------------------------------------------------------------------
|
| A designation is a job title inside a department, and `level` is the field
| that matters: the model turns it into an `order_weight` on save, and that
| weight is what every designation list is sorted by and what seniority
| comparisons read. A level outside the accepted set therefore does not just
| store odd text, it lands a row with no place in the ordering at all -- so
| the whitelist is tested both ways round.
|
*/

it('requires authentication', function () {
    $this->getJson($this->api('/designations'))->assertStatus(401);
    $this->postJson($this->api('/designations'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/designations'))->assertStatus(403);
    $this->postJson($this->api('/designations'), [])->assertStatus(403);
});

it('lists designations for a user holding Designation Index', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Index']));

    Designation::create([
        'name'          => 'Branch Manager',
        'code'          => 'BM',
        'department_id' => $chain['department']->id,
        'level'         => 'Manager',
        'is_active'     => true,
    ]);

    $response = $this->getJson($this->api('/designations'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    // Two, because departmentChain() already built the Credit Officer title.
    expect($response->json('data.data'))->toHaveCount(2);
});

it('creates a designation with every field the form sends', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $response = $this->postJson($this->api('/designations'), [
        'name'          => 'Branch Manager',
        'code'          => 'BM',
        'department_id' => $chain['department']->id,
        'level'         => 'Manager',
        'description'   => 'Runs a branch and its lending book',
        'is_active'     => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('designations', [
        'name'          => 'Branch Manager',
        'code'          => 'BM',
        'department_id' => $chain['department']->id,
        'level'         => 'Manager',
        'description'   => 'Runs a branch and its lending book',
        'is_active'     => true,
    ]);
});

it('rejects a designation with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/designations'), []),
        ['name', 'code', 'department_id', 'level']
    );
});

it('rejects a duplicate designation code', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/designations'), [
            'name'          => 'Credit Officer II',
            'code'          => $chain['designation']->code,
            'department_id' => $chain['department']->id,
            'level'         => 'mid',
        ]),
        ['code']
    );
});

it('rejects a code longer than the rule allows', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/designations'), [
            'name'          => 'Somebody',
            'code'          => str_repeat('X', 11),
            'department_id' => $chain['department']->id,
            'level'         => 'mid',
        ]),
        ['code']
    );
});

it('rejects a designation hung off a department that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/designations'), [
            'name'          => 'Nobody',
            'code'          => 'NB',
            'department_id' => 999999,
            'level'         => 'mid',
        ]),
        ['department_id']
    );
});

it('rejects a level outside the accepted set', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/designations'), [
            'name'          => 'Supreme Overlord',
            'code'          => 'SO',
            'department_id' => $chain['department']->id,
            'level'         => 'overlord',
        ]),
        ['level']
    );
});

it('accepts each level the ordering knows how to weigh', function (string $level) {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Create']));

    $this->postJson($this->api('/designations'), [
        'name'          => "A {$level} role",
        'code'          => strtoupper(substr($level, 0, 3)) . '9',
        'department_id' => $chain['department']->id,
        'level'         => $level,
    ])->assertStatus(201);

    $this->assertDatabaseHas('designations', ['level' => $level]);
})->with(['entry', 'mid', 'senior', 'lead', 'executive', 'Manager', 'Director']);

it('updates a designation without colliding with its own code', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Update']));

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/designations/') . $chain['designation']->id, [
        'name' => 'Senior Credit Officer',
        'code' => $chain['designation']->code,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('designations', [
        'id'   => $chain['designation']->id,
        'name' => 'Senior Credit Officer',
        'code' => $chain['designation']->code,
    ]);
});

it('rejects an update that takes another designation code', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Update']));

    $other = Designation::create([
        'name'          => 'Branch Manager',
        'code'          => 'BM',
        'department_id' => $chain['department']->id,
        'level'         => 'Manager',
        'is_active'     => true,
    ]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/designations/') . $chain['designation']->id, ['code' => $other->code]),
        ['code']
    );
});

it('returns 404 for a designation that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Designation Index']));

    $this->getJson($this->api('/designations/999999'))->assertStatus(404);
});

it('toggles a designation between active and inactive', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Toggle Status']));

    $this->patchJson($this->api('/designations/') . $chain['designation']->id . '/toggle-status')
        ->assertStatus(200);

    expect($chain['designation']->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Index']));

    Designation::create([
        'name'          => 'Abolished Role',
        'code'          => 'AR',
        'department_id' => $chain['department']->id,
        'level'         => 'entry',
        'is_active'     => false,
    ]);

    $response = $this->getJson($this->api('/designations/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a designation', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Designation Delete']));

    $this->deleteJson($this->api('/designations/') . $chain['designation']->id)->assertStatus(200);

    $this->assertDatabaseMissing('designations', ['id' => $chain['designation']->id]);
});
