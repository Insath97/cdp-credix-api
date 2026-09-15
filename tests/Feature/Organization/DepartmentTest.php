<?php

use App\Models\Department;
use App\Models\Employee;

/*
|--------------------------------------------------------------------------
| Organisation setup: Departments
|--------------------------------------------------------------------------
|
| Departments are the second tree in the organisation, alongside the branch
| hierarchy: an employee is posted to a branch but belongs to a department,
| and every designation hangs off one.
|
| The rule worth guarding is `head_id`. It is unique across the whole table,
| which is the schema's way of saying one person may head at most one
| department -- and the uniqueness check has to ignore the department's own
| row, or no department could ever be edited again once it had a head.
|
*/

/**
 * One employee, built with just the columns the table insists on.
 *
 * There is no Employee factory in this codebase, and the table has a NOT NULL
 * unique id_number, so the rows are built by hand with a caller-supplied
 * discriminator rather than left to chance.
 */
function departmentHead(string $suffix): Employee
{
    return Employee::create([
        'f_name'             => 'Head',
        'l_name'             => $suffix,
        'full_name'          => "Head {$suffix}",
        'name_with_initials' => "H. {$suffix}",
        'id_number'          => "NIC-{$suffix}",
        'is_active'          => true,
    ]);
}

it('requires authentication', function () {
    $this->getJson($this->api('/departments'))->assertStatus(401);
    $this->postJson($this->api('/departments'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/departments'))->assertStatus(403);
    $this->postJson($this->api('/departments'), [])->assertStatus(403);
});

it('lists departments for a user holding Department Index', function () {
    $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Index']));

    Department::create(['name' => 'Recovery', 'code' => 'REC', 'is_active' => true]);

    $response = $this->getJson($this->api('/departments'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    // Two, because departmentChain() already built the Credit department.
    expect($response->json('data.data'))->toHaveCount(2);
});

it('creates a department with every field the form sends', function () {
    $head = departmentHead('One');

    $this->actingAsApi($this->userWithPermissions(['Department Create']));

    $response = $this->postJson($this->api('/departments'), [
        'name'        => 'Recovery',
        'code'        => 'REC',
        'description' => 'Chases arrears and manages legal escalation',
        'head_id'     => $head->id,
        'is_active'   => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('departments', [
        'name'        => 'Recovery',
        'code'        => 'REC',
        'description' => 'Chases arrears and manages legal escalation',
        'head_id'     => $head->id,
        'is_active'   => true,
    ]);
});

it('rejects a department with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['Department Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/departments'), []),
        ['name', 'code']
    );
});

it('rejects a duplicate department code', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/departments'), [
            'name' => 'Credit Control',
            'code' => $chain['department']->code,
        ]),
        ['code']
    );
});

it('rejects a code longer than the rule allows', function () {
    $this->actingAsApi($this->userWithPermissions(['Department Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/departments'), [
            'name' => 'Somewhere',
            'code' => str_repeat('X', 51),
        ]),
        ['code']
    );
});

it('rejects a head who is not on the payroll', function () {
    $this->actingAsApi($this->userWithPermissions(['Department Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/departments'), [
            'name'    => 'Phantom',
            'code'    => 'PHM',
            'head_id' => 999999,
        ]),
        ['head_id']
    );
});

it('refuses to give one person a second department to head', function () {
    $head = departmentHead('Taken');

    Department::create(['name' => 'Recovery', 'code' => 'REC', 'head_id' => $head->id, 'is_active' => true]);

    $this->actingAsApi($this->userWithPermissions(['Department Create']));

    // head_id is unique across the table, so the same employee cannot show up
    // as the head of a second department.
    $this->assertValidationFailed(
        $this->postJson($this->api('/departments'), [
            'name'    => 'Legal',
            'code'    => 'LGL',
            'head_id' => $head->id,
        ]),
        ['head_id']
    );
});

it('updates a department without colliding with its own code', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Update']));

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/departments/') . $chain['department']->id, [
        'name' => 'Credit Department',
        'code' => $chain['department']->code,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('departments', [
        'id'   => $chain['department']->id,
        'name' => 'Credit Department',
        'code' => $chain['department']->code,
    ]);
});

it('lets a department keep the head it already has', function () {
    $head = departmentHead('Keeper');

    $department = Department::create(['name' => 'Recovery', 'code' => 'REC', 'head_id' => $head->id, 'is_active' => true]);

    $this->actingAsApi($this->userWithPermissions(['Department Update']));

    // The unique rule has to exclude this row, or renaming a department would
    // fail purely because it still has the head it started with.
    $response = $this->putJson($this->api('/departments/') . $department->id, [
        'name'    => 'Recovery and Legal',
        'head_id' => $head->id,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('departments', [
        'id'      => $department->id,
        'name'    => 'Recovery and Legal',
        'head_id' => $head->id,
    ]);
});

it('rejects an update that takes another department code', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Update']));

    $other = Department::create(['name' => 'Recovery', 'code' => 'REC', 'is_active' => true]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/departments/') . $chain['department']->id, ['code' => $other->code]),
        ['code']
    );
});

it('returns 404 for a department that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Department Index']));

    $this->getJson($this->api('/departments/999999'))->assertStatus(404);
});

it('toggles a department between active and inactive', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Toggle Status']));

    $this->patchJson($this->api('/departments/') . $chain['department']->id . '/toggle-status')
        ->assertStatus(200);

    expect($chain['department']->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Index']));

    Department::create(['name' => 'Disbanded', 'code' => 'DIS', 'is_active' => false]);

    $response = $this->getJson($this->api('/departments/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('serves the designations belonging to one department', function () {
    $chain = $this->departmentChain();

    $this->actingAsApi($this->userWithPermissions(['Department Index']));

    $response = $this->getJson($this->api('/departments/') . $chain['department']->id . '/designations');

    $response->assertStatus(200);
});

it('deletes a department', function () {
    $this->actingAsApi($this->userWithPermissions(['Department Delete']));

    $department = Department::create(['name' => 'Recovery', 'code' => 'REC', 'is_active' => true]);

    $this->deleteJson($this->api('/departments/') . $department->id)->assertStatus(200);

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});
