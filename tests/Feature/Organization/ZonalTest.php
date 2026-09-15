<?php

use App\Models\Zonal;

/*
|--------------------------------------------------------------------------
| Organisation setup: Zones
|--------------------------------------------------------------------------
|
| A zone groups the regions inside one province, and a branch points straight
| at a zone as well as at its region and province. That makes the zone the one
| tier referenced from two directions at once, so its code and its parent
| province are worth pinning down precisely.
|
*/

it('requires authentication', function () {
    $this->getJson($this->api('/zonals'))->assertStatus(401);
    $this->postJson($this->api('/zonals'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/zonals'))->assertStatus(403);
    $this->postJson($this->api('/zonals'), [])->assertStatus(403);
});

it('lists zones for a user holding Zonal Index', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Index']));

    Zonal::create(['name' => 'Gampaha Zone', 'code' => 'GZ', 'province_id' => $chain['province']->id, 'is_active' => true]);

    $response = $this->getJson($this->api('/zonals'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    // Two, because organisationChain() already built one zone of its own.
    expect($response->json('data.data'))->toHaveCount(2);
});

it('creates a zone with every field the form sends', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Create']));

    $response = $this->postJson($this->api('/zonals'), [
        'name'        => 'Gampaha Zone',
        'code'        => 'GZ',
        'province_id' => $chain['province']->id,
        'is_active'   => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('zonals', [
        'name'        => 'Gampaha Zone',
        'code'        => 'GZ',
        'province_id' => $chain['province']->id,
        'is_active'   => true,
    ]);
});

it('rejects a zone with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['Zonal Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/zonals'), []),
        ['name', 'code', 'province_id']
    );
});

it('rejects a duplicate zone code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/zonals'), [
            'name'        => 'Another Colombo',
            'code'        => $chain['zonal']->code,
            'province_id' => $chain['province']->id,
        ]),
        ['code']
    );
});

it('rejects a code longer than the rule allows', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/zonals'), [
            'name'        => 'Somewhere',
            'code'        => str_repeat('X', 11),
            'province_id' => $chain['province']->id,
        ]),
        ['code']
    );
});

it('rejects a zone hung off a province that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Zonal Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/zonals'), [
            'name'        => 'Nowhere Zone',
            'code'        => 'NZ',
            'province_id' => 999999,
        ]),
        ['province_id']
    );
});

it('updates a zone without colliding with its own code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Update']));

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/zonals/') . $chain['zonal']->id, [
        'name' => 'Colombo Metropolitan Zone',
        'code' => $chain['zonal']->code,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('zonals', [
        'id'   => $chain['zonal']->id,
        'name' => 'Colombo Metropolitan Zone',
        'code' => $chain['zonal']->code,
    ]);
});

it('rejects an update that takes another zone code', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Update']));

    $other = Zonal::create(['name' => 'Gampaha Zone', 'code' => 'GZ', 'province_id' => $chain['province']->id, 'is_active' => true]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/zonals/') . $chain['zonal']->id, ['code' => $other->code]),
        ['code']
    );
});

it('returns 404 for a zone that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Zonal Index']));

    $this->getJson($this->api('/zonals/999999'))->assertStatus(404);
});

it('toggles a zone between active and inactive', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Toggle Status']));

    $this->patchJson($this->api('/zonals/') . $chain['zonal']->id . '/toggle-status')
        ->assertStatus(200);

    expect($chain['zonal']->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Index']));

    Zonal::create(['name' => 'Retired Zone', 'code' => 'RZ', 'province_id' => $chain['province']->id, 'is_active' => false]);

    $response = $this->getJson($this->api('/zonals/list'));

    $response->assertStatus(200);
    // Only the chain's own active zone: the retired one must not reach a form.
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a zone', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Zonal Delete']));

    $zonal = Zonal::create(['name' => 'Gampaha Zone', 'code' => 'GZ', 'province_id' => $chain['province']->id, 'is_active' => true]);

    $this->deleteJson($this->api('/zonals/') . $zonal->id)->assertStatus(200);

    $this->assertDatabaseMissing('zonals', ['id' => $zonal->id]);
});
