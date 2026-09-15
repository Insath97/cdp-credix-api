<?php

use App\Models\Country;
use App\Models\Province;

/*
|--------------------------------------------------------------------------
| Organisation setup: Provinces
|--------------------------------------------------------------------------
|
| The second tier of the branch hierarchy. A province is the first row in the
| tree that carries a required foreign key, so it is the first place the API
| has to decide what happens when the parent it is handed does not exist --
| and every zone, region and branch below it inherits that answer.
|
*/

it('requires authentication', function () {
    $this->getJson($this->api('/provinces'))->assertStatus(401);
    $this->postJson($this->api('/provinces'), [])->assertStatus(401);
});

it('refuses a signed-in user who lacks the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/provinces'))->assertStatus(403);
    $this->postJson($this->api('/provinces'), [])->assertStatus(403);
});

it('lists provinces for a user holding Province Index', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Index']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);
    Province::create(['name' => 'Central', 'code' => 'CP', 'country_id' => $country->id, 'is_active' => true]);

    $response = $this->getJson($this->api('/provinces'));

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    expect($response->json('data.data'))->toHaveCount(2);
});

it('creates a province with every field the form sends', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Create']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    $response = $this->postJson($this->api('/provinces'), [
        'name'       => 'Western',
        'code'       => 'WP',
        'country_id' => $country->id,
        'is_active'  => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('provinces', [
        'name'       => 'Western',
        'code'       => 'WP',
        'country_id' => $country->id,
        'is_active'  => true,
    ]);
});

it('rejects a province with nothing filled in', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/provinces'), []),
        ['name', 'code', 'country_id']
    );
});

it('rejects a duplicate province code', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Create']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);

    $this->assertValidationFailed(
        $this->postJson($this->api('/provinces'), [
            'name'       => 'West',
            'code'       => 'WP',
            'country_id' => $country->id,
        ]),
        ['code']
    );
});

it('rejects a code longer than the rule allows', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Create']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);

    $this->assertValidationFailed(
        $this->postJson($this->api('/provinces'), [
            'name'       => 'Somewhere',
            'code'       => str_repeat('X', 11),
            'country_id' => $country->id,
        ]),
        ['code']
    );
});

it('rejects a province hung off a country that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Create']));

    // The row would otherwise be written against a dangling parent, which the
    // database constraint would refuse as a 500 rather than a 422.
    $this->assertValidationFailed(
        $this->postJson($this->api('/provinces'), [
            'name'       => 'Nowhere',
            'code'       => 'NW',
            'country_id' => 999999,
        ]),
        ['country_id']
    );
});

it('updates a province without colliding with its own code', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Update']));

    $country  = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    $province = Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);

    // Resending the row's own code must not read as a duplicate of itself.
    $response = $this->putJson($this->api('/provinces/') . $province->id, [
        'name' => 'Western Province',
        'code' => 'WP',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('provinces', [
        'id'   => $province->id,
        'name' => 'Western Province',
        'code' => 'WP',
    ]);
});

it('rejects an update that takes another province code', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Update']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    Province::create(['name' => 'Central', 'code' => 'CP', 'country_id' => $country->id, 'is_active' => true]);
    $province = Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);

    $this->assertValidationFailed(
        $this->putJson($this->api('/provinces/') . $province->id, ['code' => 'CP']),
        ['code']
    );
});

it('returns 404 for a province that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Index']));

    $this->getJson($this->api('/provinces/999999'))->assertStatus(404);
});

it('toggles a province between active and inactive', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Toggle Status']));

    $country  = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    $province = Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);

    $this->patchJson($this->api('/provinces/') . $province->id . '/toggle-status')
        ->assertStatus(200);

    expect($province->fresh()->is_active)->toBeFalsy();
});

it('serves the active list used by the dropdowns', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Index']));

    $country = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);
    Province::create(['name' => 'Retired', 'code' => 'RT', 'country_id' => $country->id, 'is_active' => false]);

    $response = $this->getJson($this->api('/provinces/list'));

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a province', function () {
    $this->actingAsApi($this->userWithPermissions(['Province Delete']));

    $country  = Country::create(['name' => 'Sri Lanka', 'code' => 'LK', 'is_active' => true]);
    $province = Province::create(['name' => 'Western', 'code' => 'WP', 'country_id' => $country->id, 'is_active' => true]);

    $this->deleteJson($this->api('/provinces/') . $province->id)->assertStatus(200);

    $this->assertDatabaseMissing('provinces', ['id' => $province->id]);
});
