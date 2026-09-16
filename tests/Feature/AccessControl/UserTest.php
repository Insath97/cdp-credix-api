<?php

use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Access control: users
|--------------------------------------------------------------------------
|
| Creating and editing accounts is the endpoint that hands out every other
| endpoint, because a user record carries the role and the role carries the
| permissions. Anything reachable here is reachable everywhere.
|
*/

/**
 * A staff account with the employee row behind it.
 *
 * UserController::update treats a staff user as an employee edit as well, and
 * builds the employee from whatever the body happened to contain -- so a staff
 * user with no employee row cannot be updated at all. Real staff always have
 * one, and so do the targets here.
 */
function userTestStaffUser(string $marker, array $userAttributes = []): User
{
    $employee = Employee::create([
        'f_name'             => 'Nimal',
        'l_name'             => 'Perera',
        'full_name'          => 'Nimal Perera',
        'name_with_initials' => 'N. Perera',
        'employee_code'      => 'EMP-' . $marker,
        'id_number'          => 'NIC-' . $marker,
        'phone_primary'      => '0744125923',
        'phone_secondary'    => '0744125923',
        'whatsapp_number'    => '0744125923',
        'is_active'          => true,
    ]);

    return User::factory()->create(array_merge([
        'user_type'   => 'staff',
        'employee_id' => $employee->id,
    ], $userAttributes));
}

/**
 * Grant one more permission to a user that already exists.
 *
 * userWithPermissions() takes the whole list up front, but the Super Admin
 * helper takes none, and the delete and admin-management paths need both the
 * role and a permission. Spatie's cache has to be dropped again afterwards or
 * the middleware reads the state from before the grant.
 */
function userTestGrant(User $user, string ...$permissions): User
{
    foreach ($permissions as $name) {
        $user->givePermissionTo(
            Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'api'],
                ['group_name' => 'Test Permissions'],
            )
        );
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication on every user verb', function () {
    $this->getJson($this->api('/users'))->assertStatus(401);
    $this->postJson($this->api('/users'), [])->assertStatus(401);
    $this->putJson($this->api('/users/1'), [])->assertStatus(401);
    $this->deleteJson($this->api('/users/1'))->assertStatus(401);
    $this->patchJson($this->api('/users/1/toggle-status'))->assertStatus(401);
});

it('refuses each user verb to a signed-in user holding no permission', function () {
    $target = userTestStaffUser('gating-target');

    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/users'))->assertStatus(403);
    $this->getJson($this->api('/users/') . $target->id)->assertStatus(403);
    $this->postJson($this->api('/users'), [])->assertStatus(403);
    $this->putJson($this->api('/users/') . $target->id, [])->assertStatus(403);
    $this->deleteJson($this->api('/users/') . $target->id)->assertStatus(403);
    $this->patchJson($this->api('/users/') . $target->id . '/toggle-status')->assertStatus(403);
});

it('refuses a verb to someone holding only the neighbouring permission', function () {
    // User Index must not imply User Create. Permissions that leak into each
    // other are how a read-only auditor account quietly becomes an editor.
    $target = userTestStaffUser('neighbour-target');

    $this->actingAsApi($this->userWithPermissions(['User Index']));

    $this->getJson($this->api('/users'))->assertStatus(200);
    $this->postJson($this->api('/users'), [])->assertStatus(403);
    $this->putJson($this->api('/users/') . $target->id, [])->assertStatus(403);
    $this->deleteJson($this->api('/users/') . $target->id)->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Read
|--------------------------------------------------------------------------
*/

it('lists users for a holder of User Index', function () {
    $this->actingAsApi($this->userWithPermissions(['User Index']));

    userTestStaffUser('list-a');
    userTestStaffUser('list-b');

    $response = $this->getJson($this->api('/users'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');

    // Three rows, not two: the signed-in caller is a user as well.
    expect($response->json('data.data'))->toHaveCount(3);
});

it('filters the user list by user type', function () {
    $this->actingAsApi($this->userWithPermissions(['User Index']));

    userTestStaffUser('filter-staff');

    $response = $this->getJson($this->api('/users?user_type=staff'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

it('shows a single user', function () {
    $this->actingAsApi($this->userWithPermissions(['User Index']));

    $target = userTestStaffUser('show-one');

    $response = $this->getJson($this->api('/users/') . $target->id);

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $target->id);
    $response->assertJsonPath('data.username', $target->username);

    expect($response->json('data'))->not->toHaveKey('password');
});

it('returns 404 for a user that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['User Index']));

    $this->getJson($this->api('/users/999999'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

it('creates a staff user with every field the form sends', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    $org  = $this->organisationChain();
    $dept = $this->departmentChain();

    Role::findOrCreate('Credit Officer', 'api');

    $manager = Employee::create([
        'f_name'             => 'Kamala',
        'l_name'             => 'Silva',
        'full_name'          => 'Kamala Silva',
        'name_with_initials' => 'K. Silva',
        'employee_code'      => 'EMP-MANAGER',
        'id_number'          => 'NIC-MANAGER',
        'is_active'          => true,
    ]);

    $response = $this->postJson($this->api('/users'), [
        'name'                 => 'Nimal Perera',
        'username'             => 'nperera',
        'email'                => 'nimal@example.com',
        'password'             => 'a-chosen-password',
        'user_type'            => 'staff',
        'role'                 => 'Credit Officer',
        'employee_code'        => 'EMP-0001',
        'id_number'            => '199012345678',
        'phone'                => '0744125923',
        'phone_secondary'      => '0744125923',
        'whatsapp_number'      => '0744125923',
        'branch_id'            => $org['branch']->id,
        'zonal_id'             => $org['zonal']->id,
        'region_id'            => $org['region']->id,
        'province_id'          => $org['province']->id,
        'designation_id'       => $dept['designation']->id,
        'reporting_manager_id' => $manager->id,
        'is_active'            => true,
        'can_login'            => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'username'  => 'nperera',
        'email'     => 'nimal@example.com',
        'user_type' => 'staff',
        'is_active' => true,
    ]);

    // The staff path creates the employee record alongside the account, and
    // splits the single name field into the three the employee table wants.
    $this->assertDatabaseHas('employees', [
        'employee_code'        => 'EMP-0001',
        'id_number'            => '199012345678',
        'full_name'            => 'Nimal Perera',
        'f_name'               => 'Nimal',
        'l_name'               => 'Perera',
        'name_with_initials'   => 'N. Perera',
        'branch_id'            => $org['branch']->id,
        'designation_id'       => $dept['designation']->id,
        'reporting_manager_id' => $manager->id,
    ]);

    $created = User::where('username', 'nperera')->first();

    expect($created->hasRole('Credit Officer'))->toBeTrue();

    // Whoever created the account chose the password, so the account is not
    // held by EnsurePasswordChanged the way the old NIC-defaulted ones are.
    expect($created->password_changed_at)->not->toBeNull();

    // And it is stored as a hash, never as what was typed.
    expect($created->password)->not->toBe('a-chosen-password');
});

it('defaults a staff username to the NIC when none is given', function () {
    // The NIC is an identifier, not a secret, so it is a reasonable login. It
    // is only ever a default: an explicit username wins.
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::findOrCreate('Credit Officer', 'api');

    $this->postJson($this->api('/users'), [
        'name'          => 'Sunil Bandara',
        'password'      => 'a-chosen-password',
        'user_type'     => 'staff',
        'role'          => 'Credit Officer',
        'employee_code' => 'EMP-0002',
        'id_number'     => '198712345678',
    ])->assertStatus(201);

    $this->assertDatabaseHas('users', ['username' => '198712345678']);
});

it('rejects a user with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/users'), []),
        ['name', 'password', 'user_type', 'role']
    );
});

it('requires a password of at least the minimum length', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::findOrCreate('Credit Officer', 'api');

    $this->assertValidationFailed(
        $this->postJson($this->api('/users'), [
            'name'          => 'Sunil Bandara',
            'password'      => 'short',
            'user_type'     => 'staff',
            'role'          => 'Credit Officer',
            'employee_code' => 'EMP-0003',
            'id_number'     => '198712345679',
        ]),
        ['password']
    );
});

it('rejects a role that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/users'), [
            'name'          => 'Sunil Bandara',
            'password'      => 'a-chosen-password',
            'user_type'     => 'staff',
            'role'          => 'No Such Role',
            'employee_code' => 'EMP-0004',
            'id_number'     => '198712345680',
        ]),
        ['role']
    );
});

it('rejects a duplicate username', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::findOrCreate('Credit Officer', 'api');

    $existing = userTestStaffUser('dupe-username');

    $this->assertValidationFailed(
        $this->postJson($this->api('/users'), [
            'name'          => 'Sunil Bandara',
            'username'      => $existing->username,
            'password'      => 'a-chosen-password',
            'user_type'     => 'staff',
            'role'          => 'Credit Officer',
            'employee_code' => 'EMP-0005',
            'id_number'     => '198712345681',
        ]),
        ['username']
    );
});

it('rejects a duplicate email', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::findOrCreate('Credit Officer', 'api');

    $existing = userTestStaffUser('dupe-email');

    $this->assertValidationFailed(
        $this->postJson($this->api('/users'), [
            'name'          => 'Sunil Bandara',
            'email'         => $existing->email,
            'password'      => 'a-chosen-password',
            'user_type'     => 'staff',
            'role'          => 'Credit Officer',
            'employee_code' => 'EMP-0006',
            'id_number'     => '198712345682',
        ]),
        ['email']
    );
});

it('rejects a duplicate employee code or NIC', function () {
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::findOrCreate('Credit Officer', 'api');

    userTestStaffUser('dupe-employee');

    $this->assertValidationFailed(
        $this->postJson($this->api('/users'), [
            'name'          => 'Sunil Bandara',
            'password'      => 'a-chosen-password',
            'user_type'     => 'staff',
            'role'          => 'Credit Officer',
            'employee_code' => 'EMP-dupe-employee',
            'id_number'     => 'NIC-dupe-employee',
        ]),
        ['employee_code', 'id_number']
    );
});

it('lets only a Super Admin create an admin account', function () {
    // An admin account is the one that can reach the other admin accounts, so
    // handing one out is a decision reserved to the top of the tree.
    $this->actingAsApi($this->userWithPermissions(['User Create']));

    Role::findOrCreate('Credit Officer', 'api');

    $this->postJson($this->api('/users'), [
        'name'      => 'Backdoor Admin',
        'username'  => 'backdoor',
        'password'  => 'a-chosen-password',
        'user_type' => 'admin',
        'role'      => 'Credit Officer',
    ])->assertStatus(403)
        ->assertJsonPath('message', 'Only Super Admin can create admin users');

    $this->assertDatabaseMissing('users', ['username' => 'backdoor']);
});

it('lets a Super Admin create an admin account', function () {
    $actor = userTestGrant($this->superAdmin(), 'User Create');

    $this->actingAsApi($actor);

    Role::findOrCreate('Credit Officer', 'api');

    $this->postJson($this->api('/users'), [
        'name'      => 'Second Admin',
        'username'  => 'second-admin',
        'password'  => 'a-chosen-password',
        'user_type' => 'admin',
        'role'      => 'Credit Officer',
    ])->assertStatus(201);

    $this->assertDatabaseHas('users', ['username' => 'second-admin', 'user_type' => 'admin']);
});

/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

it('updates a staff user', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $target = userTestStaffUser('update-ok');

    $response = $this->putJson($this->api('/users/') . $target->id, [
        'name'     => 'Renamed Officer',
        'username' => 'renamed-officer',
        'email'    => 'renamed@example.com',
        'phone'    => '0744125923',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id'       => $target->id,
        'name'     => 'Renamed Officer',
        'username' => 'renamed-officer',
        'email'    => 'renamed@example.com',
    ]);

    // The staff branch of update() writes the employee row as well.
    $this->assertDatabaseHas('employees', [
        'id'    => $target->employee_id,
        'phone' => '0744125923',
    ]);
});

it('does not read a user own username as a collision with itself', function () {
    // The form posts the whole record back, unchanged fields included, so the
    // uniqueness rule has to exempt the row being edited or every save of an
    // untouched username fails.
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $target = userTestStaffUser('self-username');

    $this->putJson($this->api('/users/') . $target->id, [
        'name'     => 'Same Username',
        'username' => $target->username,
        'email'    => $target->email,
    ])->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id'       => $target->id,
        'name'     => 'Same Username',
        'username' => $target->username,
    ]);
});

it('refuses an update that takes another user username', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $other  = userTestStaffUser('other-username');
    $target = userTestStaffUser('taking-username');

    $this->assertValidationFailed(
        $this->putJson($this->api('/users/') . $target->id, ['username' => $other->username]),
        ['username']
    );
});

it('refuses an update that takes another user email', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $other  = userTestStaffUser('other-email');
    $target = userTestStaffUser('taking-email');

    $this->assertValidationFailed(
        $this->putJson($this->api('/users/') . $target->id, ['email' => $other->email]),
        ['email']
    );
});

it('changes a password through the user update endpoint', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $target = userTestStaffUser('update-password');
    $target->assignRole(Role::findOrCreate('Credit Officer', 'api'));

    $this->putJson($this->api('/users/') . $target->id, [
        'password' => 'reset-by-an-admin',
    ])->assertStatus(200);

    $this->postJson($this->api('/login'), [
        'login'    => $target->username,
        'password' => 'reset-by-an-admin',
    ])->assertStatus(200);
});

it('returns 404 when updating a user that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $this->putJson($this->api('/users/999999'), ['name' => 'Nobody'])->assertStatus(404);
});

it('lets only a Super Admin update an admin account', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $target = $this->userWithPermissions([]);

    $this->putJson($this->api('/users/') . $target->id, ['name' => 'Edited By A Peer'])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Only Super Admin can manage admin users');
});

it('lets only a Super Admin promote an account to admin', function () {
    $this->actingAsApi($this->userWithPermissions(['User Update']));

    $target = userTestStaffUser('promote-to-admin');

    $this->putJson($this->api('/users/') . $target->id, ['user_type' => 'admin'])
        ->assertStatus(403);

    expect($target->fresh()->user_type)->toBe('staff');
});

/*
|--------------------------------------------------------------------------
| Privilege escalation through the role field
|--------------------------------------------------------------------------
*/

it('does not let a staff user grant themselves Super Admin', function () {
    // UpdateUserRequest accepts a `role` and UserController hands it straight
    // to syncRoles(). The only guard on that path fires when the TARGET is an
    // admin account -- so a staff user holding nothing but "User Update" can
    // PUT their OWN id with {"role": "Super Admin"} and land the role that
    // bypasses every other check in the system.
    //
    // Two things are missing and either one would close it: the role being
    // assigned is never compared against what the caller is themselves
    // entitled to hand out, and editing your own account is never treated
    // differently from editing somebody else's.
    Role::findOrCreate('Super Admin', 'api');

    $actor = userTestStaffUser('self-escalation', [
        'password_changed_at' => now(),
    ]);
    $actor = userTestGrant($actor, 'User Update');
    $actor->assignRole(Role::findOrCreate('Credit Officer', 'api'));

    $this->actingAsApi($actor->fresh());

    $response = $this->putJson($this->api('/users/') . $actor->id, [
        'role' => 'Super Admin',
    ]);

    $this->assertFalse(
        $actor->fresh()->isSuperAdmin(),
        'PRIVILEGE ESCALATION: a staff user holding only "User Update" assigned '
        . 'themselves the Super Admin role by PUTting their own user id with '
        . '{"role":"Super Admin"}. UserController::update() passes the validated '
        . 'role to syncRoles() and its only guard checks the TARGET user_type, '
        . 'which for a self-edit is the attacker\'s own "staff".'
    );

    $response->assertStatus(403);
});

it('does not let a staff user grant Super Admin to somebody else', function () {
    // The same hole from the other direction: no self-edit involved, just one
    // staff account handing the crown to a second one it controls.
    Role::findOrCreate('Super Admin', 'api');

    $actor  = userTestStaffUser('escalate-actor');
    $actor  = userTestGrant($actor, 'User Update');
    $target = userTestStaffUser('escalate-target');

    $this->actingAsApi($actor->fresh());

    $response = $this->putJson($this->api('/users/') . $target->id, [
        'role' => 'Super Admin',
    ]);

    $this->assertFalse(
        $target->fresh()->isSuperAdmin(),
        'PRIVILEGE ESCALATION: a staff user holding only "User Update" granted '
        . 'the Super Admin role to another staff account. Nothing compares the '
        . 'role being assigned against what the caller may hand out.'
    );

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Toggle status
|--------------------------------------------------------------------------
*/

it('toggles a user between active and inactive', function () {
    $this->actingAsApi($this->userWithPermissions(['User Toggle Status']));

    $target = userTestStaffUser('toggle-me');

    expect($target->is_active)->toBeTrue();

    $this->patchJson($this->api('/users/') . $target->id . '/toggle-status')
        ->assertStatus(200)
        ->assertJsonPath('data.is_active', false);

    expect($target->fresh()->is_active)->toBeFalse();

    $this->patchJson($this->api('/users/') . $target->id . '/toggle-status')
        ->assertStatus(200)
        ->assertJsonPath('data.is_active', true);

    expect($target->fresh()->is_active)->toBeTrue();
});

it('will not let a user deactivate their own account', function () {
    // Otherwise the last active administrator can lock the whole organisation
    // out of its own back office with one click.
    $actor = $this->userWithPermissions(['User Toggle Status']);

    $this->actingAsApi($actor);

    $this->patchJson($this->api('/users/') . $actor->id . '/toggle-status')
        ->assertStatus(422)
        ->assertJsonPath('message', 'You cannot deactivate your own account');

    expect($actor->fresh()->is_active)->toBeTrue();
});

it('returns 404 when toggling a user that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['User Toggle Status']));

    $this->patchJson($this->api('/users/999999/toggle-status'))->assertStatus(404);
});

it('stops a deactivated user logging in', function () {
    $this->actingAsApi($this->userWithPermissions(['User Toggle Status']));

    $target = userTestStaffUser('toggle-then-login');
    $target->assignRole(Role::findOrCreate('Credit Officer', 'api'));

    $this->patchJson($this->api('/users/') . $target->id . '/toggle-status')->assertStatus(200);

    $this->postJson($this->api('/login'), [
        'login'    => $target->username,
        'password' => 'password',
    ])->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

it('lets a Super Admin delete a user', function () {
    $actor = userTestGrant($this->superAdmin(), 'User Delete');

    $this->actingAsApi($actor);

    $target = userTestStaffUser('delete-me');

    $this->deleteJson($this->api('/users/') . $target->id)->assertStatus(200);

    $this->assertDatabaseMissing('users', ['id' => $target->id]);

    // The employee row goes with it, or the organisation chart keeps a person
    // nobody can sign in as and nobody can edit.
    $this->assertDatabaseMissing('employees', ['id' => $target->employee_id]);
});

it('refuses a delete from someone who is not a Super Admin', function () {
    $this->actingAsApi($this->userWithPermissions(['User Delete']));

    $target = userTestStaffUser('delete-refused');

    $this->deleteJson($this->api('/users/') . $target->id)
        ->assertStatus(403)
        ->assertJsonPath('message', 'Only Super Admin can delete users');

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

it('will not let a Super Admin delete their own account', function () {
    $actor = userTestGrant($this->superAdmin(), 'User Delete');

    $this->actingAsApi($actor);

    $this->deleteJson($this->api('/users/') . $actor->id)
        ->assertStatus(422)
        ->assertJsonPath('message', 'You cannot delete your own account');

    $this->assertDatabaseHas('users', ['id' => $actor->id]);
});

it('returns 404 when deleting a user that does not exist', function () {
    $actor = userTestGrant($this->superAdmin(), 'User Delete');

    $this->actingAsApi($actor);

    $this->deleteJson($this->api('/users/999999'))->assertStatus(404);
});
