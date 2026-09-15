<?php

use App\Http\Controllers\V1\SettingController;
use App\Models\Setting;

/*
|--------------------------------------------------------------------------
| System settings
|--------------------------------------------------------------------------
|
| Settings are addressed by KEY rather than by id, and each one declares its
| own type. That type decides three separate things -- how the value is
| validated on the way in, how it is written to the text column, and how it is
| cast on the way back out -- so a type the three steps disagree about produces
| a setting that accepts a change, reports success, and does not change.
|
| Several of these settings are not preferences. The recovery thresholds decide
| when a borrower is chased, and the group loan service charge is multiplied
| into real money, so a bad value here is not a cosmetic problem.
|
*/

/**
 * Put one setting into a known state.
 *
 * updateOrCreate rather than create: create_settings_table seeds all nineteen
 * production settings as part of the migration itself, so every test starts
 * with them already present and a plain insert collides on the unique key.
 * Working with the real rows is the better test anyway, since it is the real
 * declared type that decides how a value is validated, stored and cast back.
 */
function setting(string $key, string $type, $value, string $group = 'general'): Setting
{
    return Setting::updateOrCreate(
        ['key' => $key],
        [
            'value'       => (string) $value,
            'type'        => $type,
            'group'       => $group,
            'description' => 'Set up by a test.',
        ],
    );
}

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication on every settings endpoint', function () {
    $this->getJson($this->api('/settings'))->assertStatus(401);
    $this->getJson($this->api('/settings/list'))->assertStatus(401);
    $this->getJson($this->api('/settings/flags'))->assertStatus(401);
    $this->getJson($this->api('/settings/anything'))->assertStatus(401);
    $this->putJson($this->api('/settings/anything'), ['value' => 1])->assertStatus(401);
});

it('refuses reading settings to a user without Setting Index', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/settings'))->assertStatus(403);
    $this->getJson($this->api('/settings/list'))->assertStatus(403);
});

it('refuses writing a setting to a user holding only Setting Index', function () {
    setting('credit_score_grace_days', 'integer', 0, 'credit_score');

    $this->actingAsApi($this->userWithPermissions(['Setting Index']));

    $this->getJson($this->api('/settings'))->assertStatus(200);
    $this->putJson($this->api('/settings/credit_score_grace_days'), ['value' => 5])->assertStatus(403);
});

it('serves the feature flags to any signed-in user', function () {
    // Deliberately ungated: the roles that act on a switch have to be able to
    // see whether it is on, and a flag is not worth a permission of its own.
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/settings/flags'))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Reading
|--------------------------------------------------------------------------
*/

it('lists every setting', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Index']));

    $response = $this->getJson($this->api('/settings'));

    $response->assertStatus(200);

    // Counted against the table rather than a literal, because the settings
    // are seeded by the migration and the list grows as features are added.
    expect($response->json('data'))->toHaveCount(Setting::count());

    $keys = collect($response->json('data'))->pluck('key');
    expect($keys)->toContain('sms_notifications_enabled', 'group_loan_service_charge_percentage');
});

it('serves a flat key to value map cast to each declared type', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Index']));

    setting('credit_score_grace_days', 'integer', '7', 'credit_score');
    setting('sms_notifications_enabled', 'boolean', '1', 'loan_recovery');
    setting('customer_bank_list', 'json', '["Commercial Bank"]', 'customer');

    $response = $this->getJson($this->api('/settings/list'));

    $response->assertStatus(200);

    // Cast, not echoed back as the strings the column holds.
    expect($response->json('data.credit_score_grace_days'))->toBe(7);
    expect($response->json('data.sms_notifications_enabled'))->toBeTrue();
    expect($response->json('data.customer_bank_list'))->toBe(['Commercial Bank']);
});

it('shows a single setting by key', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Index']));

    setting('credit_score_grace_days', 'integer', '7', 'credit_score');

    $response = $this->getJson($this->api('/settings/credit_score_grace_days'));

    $response->assertStatus(200);
    $response->assertJsonPath('data.key', 'credit_score_grace_days');
});

it('returns 404 for a key that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Index']));

    $this->getJson($this->api('/settings/no_such_setting'))->assertStatus(404);
});

it('returns 404 when updating a key that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    $this->putJson($this->api('/settings/no_such_setting'), ['value' => 1])->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Integer settings
|--------------------------------------------------------------------------
*/

it('updates an integer setting and reads the new value straight back', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('credit_score_grace_days', 'integer', '0', 'credit_score');

    $this->putJson($this->api('/settings/credit_score_grace_days'), ['value' => 14])
        ->assertStatus(200);

    // Through the model, so the cache is exercised as well as the column.
    expect(Setting::get('credit_score_grace_days'))->toBe(14);
});

it('rejects a negative integer setting', function () {
    // Every integer setting in this table is a quantity: a day count, a point
    // value or a cap. A negative one is not a smaller limit, it is nonsense
    // that the readers clamp to zero, and zero means "no limit" for some.
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('max_loans_per_guarantor', 'integer', '2', 'guarantor');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/max_loans_per_guarantor'), ['value' => -1]),
        ['value']
    );
});

it('rejects a non-numeric value for an integer setting', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('credit_score_grace_days', 'integer', '0', 'credit_score');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/credit_score_grace_days'), ['value' => 'soon']),
        ['value']
    );
});

it('rejects an update carrying no value at all', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('credit_score_grace_days', 'integer', '0', 'credit_score');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/credit_score_grace_days'), []),
        ['value']
    );
});

/*
|--------------------------------------------------------------------------
| Boolean settings
|--------------------------------------------------------------------------
|
| The switches. Turning one OFF is the only reason a feature flag exists, and
| it used to be impossible in two independent ways at once.
|
*/

it('turns a boolean setting on with a JSON true', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('sms_notifications_enabled', 'boolean', '0', 'loan_recovery');

    $this->putJson($this->api('/settings/sms_notifications_enabled'), ['value' => true])
        ->assertStatus(200);

    expect(Setting::get('sms_notifications_enabled'))->toBeTrue();
});

it('turns a boolean setting off with a JSON false', function () {
    // The frontend sends a real boolean. This used to come back 422: the old
    // rule compared (string) $value against a list, and (string) false is the
    // empty string, which is not in it -- so `true` was accepted and `false`
    // was rejected as invalid.
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('sms_notifications_enabled', 'boolean', '1', 'loan_recovery');

    $this->putJson($this->api('/settings/sms_notifications_enabled'), ['value' => false])
        ->assertStatus(200);

    expect(Setting::get('sms_notifications_enabled'))->toBeFalse();
});

it('turns a boolean setting off with the string false', function () {
    // This one was worse: it passed validation, was stored verbatim, and then
    // read back through (bool) "false" -- which in PHP is TRUE. The API
    // answered 200 and the feature stayed on.
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('loan_revision_enabled', 'boolean', '1', 'loan_revision');

    $this->putJson($this->api('/settings/loan_revision_enabled'), ['value' => 'false'])
        ->assertStatus(200);

    expect(Setting::get('loan_revision_enabled'))->toBeFalse();
});

it('turns a boolean setting off with a zero', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('credit_score_enabled', 'boolean', '1', 'credit_score');

    $this->putJson($this->api('/settings/credit_score_enabled'), ['value' => 0])
        ->assertStatus(200);

    expect(Setting::get('credit_score_enabled'))->toBeFalse();
});

it('stores a boolean setting as 1 or 0 whatever spelling arrived', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('credit_score_enabled', 'boolean', '1', 'credit_score');

    $this->putJson($this->api('/settings/credit_score_enabled'), ['value' => 'false'])->assertStatus(200);

    // The column is text, and everything downstream casts it with (bool),
    // where the only falsy strings are "" and "0".
    expect(Setting::where('key', 'credit_score_enabled')->value('value'))->toBe('0');
});

it('rejects a value that is neither true nor false for a boolean setting', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('credit_score_enabled', 'boolean', '1', 'credit_score');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/credit_score_enabled'), ['value' => 'maybe']),
        ['value']
    );
});

it('shows a switched-off flag as false on the flags endpoint', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    foreach (SettingController::FEATURE_FLAGS as $flag) {
        setting($flag, 'boolean', '1', 'general');
    }

    $this->putJson($this->api('/settings/sms_notifications_enabled'), ['value' => false])
        ->assertStatus(200);

    $response = $this->getJson($this->api('/settings/flags'));

    $response->assertStatus(200);
    expect($response->json('data.sms_notifications_enabled'))->toBeFalse();
    expect($response->json('data.credit_score_enabled'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| JSON settings
|--------------------------------------------------------------------------
*/

it('updates a json setting with a list', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('customer_bank_list', 'json', '["Commercial Bank"]', 'customer');

    $this->putJson($this->api('/settings/customer_bank_list'), [
        'value' => ['Commercial Bank', 'Sampath Bank'],
    ])->assertStatus(200);

    expect(Setting::get('customer_bank_list'))->toBe(['Commercial Bank', 'Sampath Bank']);
});

it('rejects a scalar for a json setting', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('customer_bank_list', 'json', '["Commercial Bank"]', 'customer');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/customer_bank_list'), ['value' => 'Commercial Bank']),
        ['value']
    );
});

/*
|--------------------------------------------------------------------------
| The cache
|--------------------------------------------------------------------------
*/

it('serves the new value immediately after an update', function () {
    // Setting::get() memoises with rememberForever(), so an update that does
    // not bust the key would keep answering with the old value for the life of
    // the cache -- which, for a recovery threshold, means the nightly job keeps
    // chasing on the number nobody uses any more.
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('internal_recovery_threshold_days', 'integer', '30', 'loan_recovery');

    expect(Setting::get('internal_recovery_threshold_days'))->toBe(30);

    $this->putJson($this->api('/settings/internal_recovery_threshold_days'), ['value' => 45])
        ->assertStatus(200);

    expect(Setting::get('internal_recovery_threshold_days'))->toBe(45);
});

it('does not cache the fallback of a key that has no row yet', function () {
    // rememberForever() stores whatever the closure returns, the default
    // included. Setting::set() uses firstOrFail(), so a missing key cannot be
    // created through it either -- meaning a key read before it was seeded
    // would answer with the fallback for ever, and seeding it later changed
    // nothing.
    expect(Setting::get('not_seeded_yet', 99))->toBe(99);

    setting('not_seeded_yet', 'integer', '5', 'general');

    expect(Setting::get('not_seeded_yet', 99))->toBe(5);
});

/*
|--------------------------------------------------------------------------
| The service charge
|--------------------------------------------------------------------------
|
| Declared as a string, but read with (float) and multiplied into the amount
| every group borrower repays, so it is the one setting here that spends money.
|
*/

it('refuses a service charge percentage that is not a number', function () {
    // Stored as type 'string', which the request has no rule for, so anything
    // at all used to be accepted. GroupLoanController casts it with (float),
    // and (float) 'ten percent' is 0.0 -- every group loan approved afterwards
    // carries no service charge at all, silently.
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('group_loan_service_charge_percentage', 'decimal', '10', 'group_loan');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/group_loan_service_charge_percentage'), ['value' => 'ten percent']),
        ['value']
    );
});

it('refuses a negative service charge percentage', function () {
    // A negative charge makes the total repayment smaller than the principal:
    // the group would owe less than it borrowed.
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('group_loan_service_charge_percentage', 'decimal', '10', 'group_loan');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/group_loan_service_charge_percentage'), ['value' => '-5']),
        ['value']
    );
});

it('refuses a service charge percentage over one hundred', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('group_loan_service_charge_percentage', 'decimal', '10', 'group_loan');

    $this->assertValidationFailed(
        $this->putJson($this->api('/settings/group_loan_service_charge_percentage'), ['value' => '150']),
        ['value']
    );
});

it('accepts a sensible service charge percentage', function () {
    $this->actingAsApi($this->userWithPermissions(['Setting Update']));

    setting('group_loan_service_charge_percentage', 'decimal', '10', 'group_loan');

    $this->putJson($this->api('/settings/group_loan_service_charge_percentage'), ['value' => '12.5'])
        ->assertStatus(200);

    expect((float) Setting::get('group_loan_service_charge_percentage'))->toBe(12.5);
});
