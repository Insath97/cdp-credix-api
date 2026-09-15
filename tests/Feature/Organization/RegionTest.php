<?php

use App\Models\Region;

/*
|--------------------------------------------------------------------------
| Organisation setup: Regions
|--------------------------------------------------------------------------
|
| A region is the last tier above the branch itself, and the one the recovery
| and reporting screens group by. Its parent column is `zonal_id` -- spelled
| out in full, unlike the branch's `zone_id` -- so the tests below name it
| explicitly rather than assuming the two tiers agree.
|
*/

it('requires authentication', function () {
    $this->getJson($this->api('/regions'))->assertStatus(401);
    $this->postJson($this->api('/regions'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/regions'))->assertStatus(403);
    $this->postJson($this->api('/regions'), [])->assertStatus(403);
});

it('lists regions for a user holding Region Index', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Index']));

    Region::create(['name' => 'Colombo North', 'code' => 'CN', 'zonal_id' => $chain['zonal']->id, 'is_active' => true]);

    $response = $this->getJson($this->api('/regions'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    // Two, because organisationChain() already built one region of its own.
    expect($response->json('data.data'))->toHaveCount(2);
});

it('creates a region with every field the form sends', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Create']));

    $response = $this->postJson($this->api('/regions'), [
        'name'      => 'Colombo North',
        'code'      => 'CN',
        'zonal_id'  => $chain['zonal']->id,
        'is_active' => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('regions', [
        'name'      => 'Colombo North',
        'code'      => 'CN',
        'zonal_id'  => $chain['zonal']->id,
        'is_active' => true,
    ]);
});

it('rejects a region with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['Region Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/regions'), []),
        ['name', 'code', 'zonal_id']
    );
});

it('rejects a duplicate region code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/regions'), [
            'name'     => 'Another Central',
            'code'     => $chain['region']->code,
            'zonal_id' => $chain['zonal']->id,
        ]),
        ['code']
    );
});

it('rejects a code longer than the rule allows', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/regions'), [
            'name'     => 'Somewhere',
            'code'     => str_repeat('X', 11),
            'zonal_id' => $chain['zonal']->id,
        ]),
        ['code']
    );
});

it('rejects a region hung off a zone that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Region Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/regions'), [
            'name'     => 'Nowhere Region',
            'code'     => 'NR',
            'zonal_id' => 999999,
        ]),
        ['zonal_id']
    );
});

it('updates a region without colliding with its own code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Update']));

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/regions/') . $chain['region']->id, [
        'name' => 'Colombo Central Region',
        'code' => $chain['region']->code,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('regions', [
        'id'   => $chain['region']->id,
        'name' => 'Colombo Central Region',
        'code' => $chain['region']->code,
    ]);
});

it('rejects an update that takes another region code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Update']));

    $other = Region::create(['name' => 'Colombo North', 'code' => 'CN', 'zonal_id' => $chain['zonal']->id, 'is_active' => true]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/regions/') . $chain['region']->id, ['code' => $other->code]),
        ['code']
    );
});

it('returns 404 for a region that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Region Index']));

    $this->getJson($this->api('/regions/999999'))->assertStatus(404);
});

it('toggles a region between active and inactive', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Toggle Status']));

    $this->patchJson($this->api('/regions/') . $chain['region']->id . '/toggle-status')
        ->assertStatus(200);

    expect($chain['region']->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Index']));

    Region::create(['name' => 'Retired Region', 'code' => 'RR', 'zonal_id' => $chain['zonal']->id, 'is_active' => false]);

    $response = $this->getJson($this->api('/regions/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a region', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Region Delete']));

    $region = Region::create(['name' => 'Colombo North', 'code' => 'CN', 'zonal_id' => $chain['zonal']->id, 'is_active' => true]);

    $this->deleteJson($this->api('/regions/') . $region->id)->assertStatus(200);

    $this->assertDatabaseMissing('regions', ['id' => $region->id]);
});
