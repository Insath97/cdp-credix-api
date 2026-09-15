<?php

use App\Models\Country;
use Tests\Support\ApiTestCase;


/*
|--------------------------------------------------------------------------
| Organisation setup: Countries
|--------------------------------------------------------------------------
|
| The root of the branch hierarchy. Every province, zone, region and branch
| hangs off a country, so the rules enforced here decide what the rest of the
| organisation tree is allowed to look like.
|
*/

it('requires authentication', function () {
    $this->getJson($this->api('/countries'))->assertStatus(401);
    $this->postJson($this->api('/countries'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/countries'))->assertStatus(403);
});

it('lists countries for a user holding Country Index', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Index']));

    Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    Country::create(['name' => 'India', 'code' => 'IN', 'is_active' => true]);

    $response = $this->getJson($this->api('/countries'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    expect($response->json('data.data'))->toHaveCount(2);
});

it('creates a country with every field the form sends', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Create']));

    $response = $this->postJson($this->api('/countries'), [
        'name'        => 'Sri Lanka',
        'code'        => 'LK',
        'description' => 'Primary country of operation',
        'is_active'   => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('countries', [
        'name'        => 'Sri Lanka',
        'code'        => 'LK',
        'description' => 'Primary country of operation',
    ]);
});

it('rejects a country with no name and no code', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/countries'), []),
        ['name', 'code']
    );
});

it('rejects a duplicate country code', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Create']));

    Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    $this->assertValidationFailed(
        $this->postJson($this->api('/countries'), ['name' => 'Lanka', 'code' => 'LK']),
        ['code']
    );
});

it('rejects a code longer than the column holds', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/countries'), [
            'name' => 'Somewhere',
            'code' => str_repeat('X', 11),
        ]),
        ['code']
    );
});

it('updates a country without colliding with its own code', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Update']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/countries/') . $country->id, [
        'name' => 'Democratic Socialist Republic of Sri Lanka',
        'code' => 'LK',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('countries', [
        'id'   => $country->id,
        'name' => 'Democratic Socialist Republic of Sri Lanka',
        'code' => 'LK',
    ]);
});

it('rejects an update that takes another country code', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Update']));

    Country::create(['name' => 'India', 'code' => 'IN', 'is_active' => true]);
    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/countries/') . $country->id, ['code' => 'IN']),
        ['code']
    );
});

it('returns 404 for a country that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Index']));

    $this->getJson($this->api('/countries/999999'))->assertStatus(404);
});

it('toggles a country between active and inactive', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Toggle Status']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    $this->patchJson($this->api('/countries/') . $country->id . '/toggle-status')
        ->assertStatus(200);

    expect($country->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Index']));

    Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    Country::create(['name' => 'Retired', 'code' => 'RT', 'is_active' => false]);

    $response = $this->getJson($this->api('/countries/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a country', function () {
    $this->actingAsApi($this->userWithPermissions(['Country Delete']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    $this->deleteJson($this->api('/countries/') . $country->id)->assertStatus(200);
});
