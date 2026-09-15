<?php

use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Access control: roles
|--------------------------------------------------------------------------
|
| A role is a named bundle of permissions, and a user's access is the union of
| the bundles they hold. Editing one therefore changes what every holder can
| do, silently and immediately -- which is why the endpoints below are gated
| and why the seeded roles refuse to be deleted.
|
*/

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication on every role verb', function () {
    $this->getJson($this->api('/roles'))->assertStatus(401);
    $this->postJson($this->api('/roles'), [])->assertStatus(401);
    $this->putJson($this->api('/roles/1'), [])->assertStatus(401);
    $this->deleteJson($this->api('/roles/1'))->assertStatus(401);
    $this->getJson($this->api('/roles/list'))->assertStatus(401);
});

it('refuses each role verb to a signed-in user holding no permission', function () {
    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/roles'))->assertStatus(403);
    $this->getJson($this->api('/roles/') . $role->id)->assertStatus(403);
    $this->postJson($this->api('/roles'), [])->assertStatus(403);
    $this->putJson($this->api('/roles/') . $role->id, [])->assertStatus(403);
    $this->deleteJson($this->api('/roles/') . $role->id)->assertStatus(403);
});

it('refuses a role verb to someone holding only the neighbouring permission', function () {
    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    $this->getJson($this->api('/roles'))->assertStatus(200);
    $this->postJson($this->api('/roles'), [])->assertStatus(403);
    $this->putJson($this->api('/roles/') . $role->id, [])->assertStatus(403);
    $this->deleteJson($this->api('/roles/') . $role->id)->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Read
|--------------------------------------------------------------------------
*/

it('lists roles for a holder of Role Index', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    Role::create(['name' => 'Credit Officer', 'guard_name' => 'api']);

    $response = $this->getJson($this->api('/roles'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    expect($response->json('data.data'))->toHaveCount(2);
});

it('searches roles by name', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    Role::create(['name' => 'Credit Officer', 'guard_name' => 'api']);

    $response = $this->getJson($this->api('/roles?search=Credit'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

it('shows a single role with its permissions', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    $role->syncPermissions([$this->permission('Branch Index')]);

    $response = $this->getJson($this->api('/roles/') . $role->id);

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Branch Manager');
    expect($response->json('data.permissions'))->toHaveCount(1);
});

it('returns 404 for a role that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    $this->getJson($this->api('/roles/999999'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

it('creates a role with the permissions it was given', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Create']));

    $index  = $this->permission('Branch Index');
    $create = $this->permission('Branch Create');

    $response = $this->postJson($this->api('/roles'), [
        'name'        => 'Branch Manager',
        'permissions' => [$index->id, $create->id],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('roles', ['name' => 'Branch Manager', 'guard_name' => 'api']);

    $role = Role::where('name', 'Branch Manager')->first();

    expect($role->permissions)->toHaveCount(2);

    // Always the api guard, whatever the caller asked for: a role minted on
    // the web guard would be invisible to every route in this API and would
    // look, on the roles screen, exactly like one that works.
    expect($role->guard_name)->toBe('api');
});

it('rejects a role with no name and no permissions', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/roles'), []),
        ['name', 'permissions']
    );
});

it('rejects a duplicate role name', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Create']));

    Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $this->assertValidationFailed(
        $this->postJson($this->api('/roles'), [
            'name'        => 'Branch Manager',
            'permissions' => [$this->permission('Branch Index')->id],
        ]),
        ['name']
    );
});

it('rejects a permission id that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/roles'), [
            'name'        => 'Branch Manager',
            'permissions' => [999999],
        ]),
        ['permissions.0']
    );
});

it('never lets a caller mark its own new role as protected', function () {
    // is_protected is the flag destroy() refuses on, so it is set by the seeder
    // and by nothing else. A role able to protect itself would be a role nobody
    // could delete, and the update path being able to CLEAR it would make the
    // protection on the seeded Super Admin worth nothing. The request is
    // accepted, the field is ignored.
    $this->actingAsApi($this->userWithPermissions(['Role Create']));

    $this->postJson($this->api('/roles'), [
        'name'         => 'Super Admin Clone',
        'permissions'  => [$this->permission('Branch Index')->id],
        'is_protected' => true,
    ])->assertStatus(201);

    expect(Role::where('name', 'Super Admin Clone')->first()->is_protected)->toBeFalsy();
});

it('never lets a caller clear the protection on a seeded role', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Update', 'Role Delete']));

    $role = Role::create([
        'name'         => 'Seeded Admin',
        'guard_name'   => 'api',
        'is_protected' => true,
    ]);

    $this->putJson($this->api('/roles/') . $role->id, [
        'name'         => 'Seeded Admin',
        'is_protected' => false,
    ])->assertStatus(200);

    expect($role->fresh()->is_protected)->toBeTruthy();

    // And it is still undeletable afterwards, which is the whole point.
    $this->deleteJson($this->api('/roles/') . $role->id)->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

it('renames a role and replaces its permissions', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Update']));

    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    $role->syncPermissions([$this->permission('Branch Index')]);

    $replacement = $this->permission('Branch Delete');

    $response = $this->putJson($this->api('/roles/') . $role->id, [
        'name'        => 'Regional Manager',
        'permissions' => [$replacement->id],
    ]);

    $response->assertStatus(200);

    $role->refresh()->load('permissions');

    expect($role->name)->toBe('Regional Manager');

    // sync, not attach: a permission dropped from the form has to be dropped
    // from the role, or nothing can ever be taken away.
    expect($role->permissions->pluck('name')->all())->toBe(['Branch Delete']);
});

it('does not read a role own name as a collision with itself', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Update']));

    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $this->putJson($this->api('/roles/') . $role->id, [
        'name'        => 'Branch Manager',
        'permissions' => [$this->permission('Branch Index')->id],
    ])->assertStatus(200);
});

it('refuses an update that takes another role name', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Update']));

    Role::create(['name' => 'Credit Officer', 'guard_name' => 'api']);
    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $this->assertValidationFailed(
        $this->putJson($this->api('/roles/') . $role->id, ['name' => 'Credit Officer']),
        ['name']
    );
});

it('returns 404 when updating a role that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Update']));

    $this->putJson($this->api('/roles/999999'), ['name' => 'Nobody'])->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

it('deletes a role that nobody holds', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Delete']));

    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $this->deleteJson($this->api('/roles/') . $role->id)->assertStatus(200);

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('refuses to delete a protected system role', function () {
    // The seeded roles are what the system's own guards name. Deleting one
    // does not fail loudly -- it quietly turns every "unless they are the
    // Super Admin" check into a check nobody can satisfy.
    $this->actingAsApi($this->userWithPermissions(['Role Delete']));

    $role = Role::create([
        'name'         => 'Super Admin',
        'guard_name'   => 'api',
        'is_protected' => true,
    ]);

    $response = $this->deleteJson($this->api('/roles/') . $role->id);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'Cannot delete protected system role: Super Admin');
    $response->assertJsonPath('data.protected', true);

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

it('refuses to delete a role that is assigned to a user', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Delete']));

    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $holder = $this->userWithPermissions([]);
    $holder->assignRole($role);

    $response = $this->deleteJson($this->api('/roles/') . $role->id);

    $response->assertStatus(422);
    $response->assertJsonPath('data.assigned_users_count', 1);

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

it('returns 404 when deleting a role that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Delete']));

    $this->deleteJson($this->api('/roles/999999'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| The roles/list endpoint
|--------------------------------------------------------------------------
*/

it('serves the role list used by the user form', function () {
    // The dropdown on the user screen, reached by someone holding a User
    // permission rather than a Role one, so the gate accepts either.
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    Role::create(['name' => 'Credit Officer', 'guard_name' => 'api']);

    $response = $this->getJson($this->api('/roles/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0'))->toHaveKeys(['id', 'name', 'guard_name']);
});

it('does not hand the role list to just any signed-in user', function () {
    // It used to. getAvailableRoles was in none of the middleware only: lists,
    // so any authenticated principal could enumerate every role in the system.
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/roles/list'))->assertStatus(403);
});

it('does not hand the role list to a customer', function () {
    $this->actingAsApi($this->customerUser());

    $this->getJson($this->api('/roles/list'))->assertStatus(403);
});

it('never offers Super Admin in the role list', function () {
    // A role picker that lists Super Admin lets one administrator hand out
    // full control of the system. The comparison is case-insensitive on
    // purpose: the role is seeded as 'SUPER ADMIN' and a plain != missed it.
    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    Role::create(['name' => 'SUPER ADMIN', 'guard_name' => 'api']);
    Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);

    $response = $this->getJson($this->api('/roles/list'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Branch Manager']);
});

it('leaves roles on other guards out of the role list', function () {
    $this->actingAsApi($this->userWithPermissions(['Role Index']));

    Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    Role::create(['name' => 'Web Only', 'guard_name' => 'web']);

    $response = $this->getJson($this->api('/roles/list'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('name')->all())->toBe(['Branch Manager']);
});
