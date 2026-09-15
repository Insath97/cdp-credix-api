<?php

use App\Models\Branch;
use App\Models\Group;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Organisation setup: Branches
|--------------------------------------------------------------------------
|
| The leaf of the hierarchy and the row everything operational hangs off: an
| employee is posted to a branch, a loan is booked at one, and the reports
| group by one. It is also the only tier that points at three parents at once
| (zone, region and province) with no check that the three agree, and the only
| one carrying free-text contact details and map coordinates, so the field
| rules below are what stand between the branch table and nonsense.
|
| Note the column is `zone_id`, not `zonal_id`, even though it references the
| zonals table. The tests name it as the API does.
|
*/

it('requires authentication', function () {
    $this->getJson($this->api('/branches'))->assertStatus(401);
    $this->postJson($this->api('/branches'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/branches'))->assertStatus(403);
    $this->postJson($this->api('/branches'), [])->assertStatus(403);
});

it('lists branches for a user holding Branch Index', function () {
    $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Index']));

    $response = $this->getJson($this->api('/branches'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    expect($response->json('data.data'))->toHaveCount(1);
});

it('creates a branch with every field the form sends', function () {
    $chain = $this->organisationChain();
    $group = Group::create(['name' => 'Central Cluster', 'code' => 'CCL', 'is_active' => true]);

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $response = $this->postJson($this->api('/branches'), [
        'name'            => 'Kandy City Branch',
        'code'            => 'KDY-001',
        'group_id'        => $group->id,
        'address_line1'   => '10 Peradeniya Road',
        'address_line2'   => 'Level 2',
        'city'            => 'Kandy',
        'postal_code'     => '20000',
        'zone_id'         => $chain['zonal']->id,
        'region_id'       => $chain['region']->id,
        'province_id'     => $chain['province']->id,
        'phone_primary'   => '0812345678',
        'phone_secondary' => '0812345679',
        'email'           => 'kandy@credix.test',
        'fax'             => '0812345670',
        'opening_date'    => '2021-06-01',
        'branch_type'     => 'city',
        'latitude'        => 7.2906,
        'longitude'       => 80.6337,
        'is_active'       => true,
        'is_head_office'  => false,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('branches', [
        'name'            => 'Kandy City Branch',
        'code'            => 'KDY-001',
        'group_id'        => $group->id,
        'address_line1'   => '10 Peradeniya Road',
        'address_line2'   => 'Level 2',
        'city'            => 'Kandy',
        'postal_code'     => '20000',
        'zone_id'         => $chain['zonal']->id,
        'region_id'       => $chain['region']->id,
        'province_id'     => $chain['province']->id,
        'phone_primary'   => '0812345678',
        'phone_secondary' => '0812345679',
        'email'           => 'kandy@credix.test',
        'fax'             => '0812345670',
        'branch_type'     => 'city',
        'is_active'       => true,
        'is_head_office'  => false,
    ]);

    // The date and the coordinates are read back through the model rather than
    // matched as raw column values: both are cast, so the stored text is a
    // storage detail the test has no business asserting on.
    $branch = Branch::where('code', 'KDY-001')->firstOrFail();

    expect($branch->opening_date->toDateString())->toBe('2021-06-01');
    expect((float) $branch->latitude)->toBe(7.2906);
    expect((float) $branch->longitude)->toBe(80.6337);
});

it('creates a branch when the optional fields are simply left out', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    // address_line2, postal_code, phone_secondary, fax and group_id are all
    // nullable, so a form that never collected them must still go through.
    $response = $this->postJson($this->api('/branches'), [
        'name'          => 'Matara Satellite',
        'code'          => 'MTR-001',
        'address_line1' => '5 Beach Road',
        'city'          => 'Matara',
        'zone_id'       => $chain['zonal']->id,
        'region_id'     => $chain['region']->id,
        'province_id'   => $chain['province']->id,
        'phone_primary' => '0412345678',
        'opening_date'  => '2022-03-15',
        'branch_type'   => 'satellite',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('branches', [
        'code'            => 'MTR-001',
        'address_line2'   => null,
        'postal_code'     => null,
        'phone_secondary' => null,
        'fax'             => null,
        'group_id'        => null,
    ]);
});

it('rejects a branch with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), []),
        [
            'name',
            'code',
            'address_line1',
            'city',
            'zone_id',
            'region_id',
            'province_id',
            'phone_primary',
            'opening_date',
            'branch_type',
        ]
    );
});

it('rejects a duplicate branch code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), [
            'name'          => 'Impostor',
            'code'          => $chain['branch']->code,
            'address_line1' => '1 Somewhere',
            'city'          => 'Colombo',
            'zone_id'       => $chain['zonal']->id,
            'region_id'     => $chain['region']->id,
            'province_id'   => $chain['province']->id,
            'phone_primary' => '0112345678',
            'opening_date'  => '2023-01-01',
            'branch_type'   => 'main',
        ]),
        ['code']
    );
});

it('rejects a code longer than the rule allows', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), [
            'name'          => 'Too Long',
            'code'          => str_repeat('X', 21),
            'address_line1' => '1 Somewhere',
            'city'          => 'Colombo',
            'zone_id'       => $chain['zonal']->id,
            'region_id'     => $chain['region']->id,
            'province_id'   => $chain['province']->id,
            'phone_primary' => '0112345678',
            'opening_date'  => '2023-01-01',
            'branch_type'   => 'main',
        ]),
        ['code']
    );
});

it('rejects a branch pointed at a zone, region or province that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), [
            'name'          => 'Nowhere Branch',
            'code'          => 'NWH-001',
            'address_line1' => '1 Nowhere',
            'city'          => 'Nowhere',
            'zone_id'       => 999999,
            'region_id'     => 999999,
            'province_id'   => 999999,
            'phone_primary' => '0112345678',
            'opening_date'  => '2023-01-01',
            'branch_type'   => 'main',
        ]),
        ['zone_id', 'region_id', 'province_id']
    );
});

it('rejects a branch type outside the four the column allows', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    // The column is an enum, so anything the request lets through would be
    // written as an empty string or refused by the database as a 500.
    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), [
            'name'          => 'Pop Up',
            'code'          => 'POP-001',
            'address_line1' => '1 Somewhere',
            'city'          => 'Colombo',
            'zone_id'       => $chain['zonal']->id,
            'region_id'     => $chain['region']->id,
            'province_id'   => $chain['province']->id,
            'phone_primary' => '0112345678',
            'opening_date'  => '2023-01-01',
            'branch_type'   => 'popup',
        ]),
        ['branch_type']
    );
});

it('accepts each of the four branch types', function (string $type) {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->postJson($this->api('/branches'), [
        'name'          => "A {$type} branch",
        'code'          => strtoupper(substr($type, 0, 3)) . '-900',
        'address_line1' => '1 Somewhere',
        'city'          => 'Colombo',
        'zone_id'       => $chain['zonal']->id,
        'region_id'     => $chain['region']->id,
        'province_id'   => $chain['province']->id,
        'phone_primary' => '0112345678',
        'opening_date'  => '2023-01-01',
        'branch_type'   => $type,
    ])->assertStatus(201);

    $this->assertDatabaseHas('branches', ['branch_type' => $type]);
})->with(['main', 'city', 'satellite', 'mobile']);

it('rejects coordinates that are not on the globe', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), [
            'name'          => 'Off World',
            'code'          => 'OFF-001',
            'address_line1' => '1 Somewhere',
            'city'          => 'Colombo',
            'zone_id'       => $chain['zonal']->id,
            'region_id'     => $chain['region']->id,
            'province_id'   => $chain['province']->id,
            'phone_primary' => '0112345678',
            'opening_date'  => '2023-01-01',
            'branch_type'   => 'main',
            'latitude'      => 120,
            'longitude'     => 200,
        ]),
        ['latitude', 'longitude']
    );
});

it('rejects a malformed email and a non-date opening date', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/branches'), [
            'name'          => 'Bad Contact',
            'code'          => 'BAD-001',
            'address_line1' => '1 Somewhere',
            'city'          => 'Colombo',
            'zone_id'       => $chain['zonal']->id,
            'region_id'     => $chain['region']->id,
            'province_id'   => $chain['province']->id,
            'phone_primary' => '0112345678',
            'email'         => 'not-an-email',
            'opening_date'  => 'sometime last year',
            'branch_type'   => 'main',
        ]),
        ['email', 'opening_date']
    );
});

it('updates a branch without colliding with its own code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Update']));

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/branches/') . $chain['branch']->id, [
        'name' => 'Head Office (Colombo)',
        'code' => $chain['branch']->code,
        'city' => 'Colombo 03',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('branches', [
        'id'   => $chain['branch']->id,
        'name' => 'Head Office (Colombo)',
        'code' => $chain['branch']->code,
        'city' => 'Colombo 03',
    ]);
});

it('rejects an update that takes another branch code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Update']));

    $other = Branch::create([
        'name'          => 'Galle Branch',
        'code'          => 'GAL-001',
        'address_line1' => '3 Lighthouse Street',
        'city'          => 'Galle',
        'zone_id'       => $chain['zonal']->id,
        'region_id'     => $chain['region']->id,
        'province_id'   => $chain['province']->id,
        'phone_primary' => '0912345678',
        'opening_date'  => '2019-05-05',
        'branch_type'   => 'city',
        'is_active'     => true,
    ]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/branches/') . $chain['branch']->id, ['code' => $other->code]),
        ['code']
    );
});

it('returns 404 for a branch that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Branch Index']));

    $this->getJson($this->api('/branches/999999'))->assertStatus(404);
});

it('toggles a branch between active and inactive', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Toggle Status']));

    $this->patchJson($this->api('/branches/') . $chain['branch']->id . '/toggle-status')
        ->assertStatus(200);

    expect($chain['branch']->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Branch Index']));

    Branch::create([
        'name'          => 'Closed Branch',
        'code'          => 'CLS-001',
        'address_line1' => '9 Old Road',
        'city'          => 'Colombo',
        'zone_id'       => $chain['zonal']->id,
        'region_id'     => $chain['region']->id,
        'province_id'   => $chain['province']->id,
        'phone_primary' => '0112345600',
        'opening_date'  => '2015-01-01',
        'branch_type'   => 'main',
        'is_active'     => false,
    ]);

    $response = $this->getJson($this->api('/branches/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('refuses a branch delete from anyone but the Super Admin', function () {
    $chain = $this->organisationChain();

    // Holding Branch Delete is not enough. The controller reserves the delete
    // for the Super Admin on top of the permission, because a branch cascades
    // to everything booked at it.
    $this->actingAsApi($this->userWithPermissions(['Branch Delete']));

    $this->deleteJson($this->api('/branches/') . $chain['branch']->id)->assertStatus(403);

    $this->assertDatabaseHas('branches', ['id' => $chain['branch']->id]);
});

it('deletes a branch for a Super Admin holding Branch Delete', function () {
    $chain = $this->organisationChain();

    // The permission middleware runs first and the Super Admin role is not a
    // blanket bypass here, so the account needs both to get through.
    $admin = $this->superAdmin();
    $admin->givePermissionTo($this->permission('Branch Delete'));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAsApi($admin->fresh());

    $this->deleteJson($this->api('/branches/') . $chain['branch']->id)->assertStatus(200);

    $this->assertDatabaseMissing('branches', ['id' => $chain['branch']->id]);
});
