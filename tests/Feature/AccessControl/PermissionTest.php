<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Access control: permissions
|--------------------------------------------------------------------------
|
| A permission is only a name until a route quotes it. The middleware matches
| on that string exactly, so renaming one here silently detaches it from the
| endpoint it was guarding -- which is why these endpoints are gated as
| tightly as the roles they feed.
|
*/

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication on every permission verb', function () {
    $this->getJson($this->api('/permissions'))->assertStatus(401);
    $this->postJson($this->api('/permissions'), [])->assertStatus(401);
    $this->putJson($this->api('/permissions/1'), [])->assertStatus(401);
    $this->deleteJson($this->api('/permissions/1'))->assertStatus(401);
    $this->getJson($this->api('/permissions/list'))->assertStatus(401);
});

it('refuses each permission verb to a signed-in user holding no permission', function () {
    $subject = $this->permission('Branch Index');

    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/permissions'))->assertStatus(403);
    $this->getJson($this->api('/permissions/') . $subject->id)->assertStatus(403);
    $this->postJson($this->api('/permissions'), [])->assertStatus(403);
    $this->putJson($this->api('/permissions/') . $subject->id, [])->assertStatus(403);
    $this->deleteJson($this->api('/permissions/') . $subject->id)->assertStatus(403);
});

it('refuses a permission verb to someone holding only the neighbouring permission', function () {
    $subject = $this->permission('Branch Index');

    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $this->getJson($this->api('/permissions'))->assertStatus(200);
    $this->postJson($this->api('/permissions'), [])->assertStatus(403);
    $this->putJson($this->api('/permissions/') . $subject->id, [])->assertStatus(403);
    $this->deleteJson($this->api('/permissions/') . $subject->id)->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Read
|--------------------------------------------------------------------------
*/

it('lists permissions for a holder of Permission Index', function () {
    // The caller's own Permission Index row counts, so the fixtures are
    // created on top of it and the expected count says so.
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $this->permission('Branch Index');
    $this->permission('Branch Create');

    $response = $this->getJson($this->api('/permissions'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    expect($response->json('data.data'))->toHaveCount(3);
});

it('filters permissions by group name', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    Permission::create(['name' => 'Loan Index', 'guard_name' => 'api', 'group_name' => 'Lending']);

    $response = $this->getJson($this->api('/permissions?group_name=Lending'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

it('searches permissions by name', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $this->permission('Branch Index');

    $response = $this->getJson($this->api('/permissions?search=Branch'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

it('shows a single permission', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $subject = $this->permission('Branch Index');

    $response = $this->getJson($this->api('/permissions/') . $subject->id);

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Branch Index');
});

it('returns 404 for a permission that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $this->getJson($this->api('/permissions/999999'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

it('creates a permission', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Create']));

    $response = $this->postJson($this->api('/permissions'), [
        'name'       => 'Loan Approve',
        'group_name' => 'Lending',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('permissions', [
        'name'       => 'Loan Approve',
        'group_name' => 'Lending',
        // Forced by the controller regardless of what was posted. A permission
        // minted on another guard would never match the middleware, and would
        // sit on the roles screen looking like one that does.
        'guard_name' => 'api',
    ]);
});

it('forces the api guard even when another one is posted', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Create']));

    $this->postJson($this->api('/permissions'), [
        'name'       => 'Loan Approve',
        'group_name' => 'Lending',
        'guard_name' => 'web',
    ])->assertStatus(201);

    $this->assertDatabaseHas('permissions', ['name' => 'Loan Approve', 'guard_name' => 'api']);
});

it('rejects a permission with no name and no group', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/permissions'), []),
        ['name', 'group_name']
    );
});

it('rejects a duplicate permission name', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Create']));

    $this->permission('Branch Index');

    $this->assertValidationFailed(
        $this->postJson($this->api('/permissions'), [
            'name'       => 'Branch Index',
            'group_name' => 'Organisation',
        ]),
        ['name']
    );
});

/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

it('renames a permission and moves its group', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Update']));

    $subject = $this->permission('Branch Index');

    $this->putJson($this->api('/permissions/') . $subject->id, [
        'name'       => 'Branch List',
        'group_name' => 'Organisation',
    ])->assertStatus(200);

    $this->assertDatabaseHas('permissions', [
        'id'         => $subject->id,
        'name'       => 'Branch List',
        'group_name' => 'Organisation',
    ]);
});

it('does not read a permission own name as a collision with itself', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Update']));

    $subject = $this->permission('Branch Index');

    $this->putJson($this->api('/permissions/') . $subject->id, [
        'name'       => 'Branch Index',
        'group_name' => 'Organisation',
    ])->assertStatus(200);
});

it('refuses an update that takes another permission name', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Update']));

    $this->permission('Branch Create');
    $subject = $this->permission('Branch Index');

    $this->assertValidationFailed(
        $this->putJson($this->api('/permissions/') . $subject->id, ['name' => 'Branch Create']),
        ['name']
    );
});

it('will not let an update move a permission off the api guard', function () {
    // The controller strips guard_name from the payload on purpose: a
    // permission that changes guard stops matching the middleware that quotes
    // it, and every role holding it silently loses the endpoint.
    $this->actingAsApi($this->userWithPermissions(['Permission Update']));

    $subject = $this->permission('Branch Index');

    $this->putJson($this->api('/permissions/') . $subject->id, [
        'name'       => 'Branch Index',
        'guard_name' => 'web',
    ])->assertStatus(200);

    expect($subject->fresh()->guard_name)->toBe('api');
});

it('returns 404 when updating a permission that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Update']));

    $this->putJson($this->api('/permissions/999999'), ['name' => 'Nobody'])->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

it('deletes a permission that no role holds', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Delete']));

    $subject = $this->permission('Branch Index');

    $this->deleteJson($this->api('/permissions/') . $subject->id)->assertStatus(200);

    $this->assertDatabaseMissing('permissions', ['id' => $subject->id]);
});

it('refuses to delete a permission that a role still holds', function () {
    // Deleting it would not announce itself: the role simply stops granting
    // the endpoint, and the first anyone hears of it is a user who used to be
    // able to do their job.
    $this->actingAsApi($this->userWithPermissions(['Permission Delete']));

    $subject = $this->permission('Branch Index');

    $role = Role::create(['name' => 'Branch Manager', 'guard_name' => 'api']);
    $role->syncPermissions([$subject]);

    $response = $this->deleteJson($this->api('/permissions/') . $subject->id);

    $response->assertStatus(422);
    $response->assertJsonPath('data.assigned_roles_count', 1);

    $this->assertDatabaseHas('permissions', ['id' => $subject->id]);
});

it('returns 404 when deleting a permission that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Delete']));

    $this->deleteJson($this->api('/permissions/999999'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| The permissions/list endpoint
|--------------------------------------------------------------------------
*/

it('serves the permission list to a holder of Permission Index', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    Permission::create(['name' => 'Loan Index', 'guard_name' => 'api', 'group_name' => 'Lending']);
    Permission::create(['name' => 'Branch Index', 'guard_name' => 'api', 'group_name' => 'Organisation']);

    $response = $this->getJson($this->api('/permissions/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);

    // Grouped, because the role screen renders one section per group. Ordered
    // by group first, so Lending sorts above Organisation and above the
    // 'Test Permissions' group the harness puts the caller's own rights in.
    expect($response->json('data.0'))->toHaveKeys(['id', 'name', 'group_name']);
    expect($response->json('data.0.group_name'))->toBe('Lending');
});

it('also serves the permission list to someone who may edit a role', function () {
    // The checkbox tree on the role form. Whoever edits a role reaches this
    // holding a Role permission, not a Permission one, so the gate has to
    // accept either -- and did not used to accept anything at all.
    $this->actingAsApi($this->userWithPermissions(['Role Update']));

    $this->getJson($this->api('/permissions/list'))->assertStatus(200);
});

it('does not hand the permission catalogue to just any signed-in user', function () {
    // It used to. getPermissionList was in none of the middleware only: lists,
    // so every authenticated principal -- a customer's portal token included --
    // could read a precise map of everything this system can do.
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/permissions/list'))->assertStatus(403);
});

it('does not hand the permission catalogue to a customer', function () {
    $this->actingAsApi($this->customerUser());

    $this->getJson($this->api('/permissions/list'))->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Listing shape
|--------------------------------------------------------------------------
*/

it('returns a paginator even when the filter matches nothing', function () {
    // The empty branch used to answer with a bare [] while every other result
    // was a paginator object, so `data` changed shape with the row count and
    // the client had no total or last_page to draw its pager from.
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $response = $this->getJson($this->api('/permissions?group_name=No Such Group'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toBe([]);
    expect($response->json('data.total'))->toBe(0);
    $response->assertJsonStructure(['data' => ['data', 'total', 'per_page', 'current_page', 'last_page']]);
});

it('ignores a group filter that was cleared rather than matching on empty', function () {
    // A cleared dropdown sends `?group_name=`. has() is true for that, so the
    // query became `where group_name = ''` and the screen reported no
    // permissions at all for a filter the user had just reset.
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    Permission::create(['name' => 'Loan Index', 'guard_name' => 'api', 'group_name' => 'Lending']);

    $response = $this->getJson($this->api('/permissions?group_name=&guard_name='));

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBeGreaterThan(0);
});

it('does not fall over on a per_page of zero', function () {
    // per_page=0 reached LengthAwarePaginator, which divides by the page size;
    // the DivisionByZeroError came back through the catch-all as a bare 500.
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $this->getJson($this->api('/permissions?per_page=0'))->assertStatus(200);
});

it('caps an oversized per_page rather than serving the whole table', function () {
    $this->actingAsApi($this->userWithPermissions(['Permission Index']));

    $response = $this->getJson($this->api('/permissions?per_page=100000'));

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(100);
});
