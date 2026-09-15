<?php

use App\Models\Customer;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Registering a customer
|--------------------------------------------------------------------------
|
| One request writes a great deal more than one row. Alongside the customer it
| mints a portal login, and it can carry bank details, fixed and moving assets,
| liabilities, guarantors and their documents, all in the same payload.
|
| That makes two things worth pinning. Every branch of that fan-out has to land
| attached to the right customer, and a failure anywhere in it has to take the
| whole thing back out -- a half-registered customer with a login and no
| guarantors is worse than a rejected form.
|
*/

/**
 * The smallest payload the form will accept.
 */
function customerPayload(int $branchId, array $overrides = []): array
{
    return array_merge([
        'full_name'          => 'Nimal Perera',
        'name_with_initials' => 'N. Perera',
        'id_type'            => 'nic',
        'id_number'          => '199012345678',
        'address_line_1'     => '42 Galle Road',
        'country'            => 'Sri Lanka',
        'date_of_birth'      => '1990-05-20',
        'phone_primary'      => '0771234567',
        'have_whatsapp'      => false,
        'preferred_language' => 'en',
        'branch_id'          => $branchId,
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication to register a customer', function () {
    $this->postJson($this->api('/customers'), [])->assertStatus(401);
});

it('refuses registration to a user without Customer Create', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->userWithPermissions(['Customer Index']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id))
        ->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| The customer record
|--------------------------------------------------------------------------
*/

it('registers a customer from the minimum the form requires', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $response = $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id));

    $response->assertStatus(201);

    $this->assertDatabaseHas('customers', [
        'full_name'     => 'Nimal Perera',
        'id_number'     => '199012345678',
        'phone_primary' => '0771234567',
        'branch_id'     => $chain['branch']->id,
    ]);
});

it('mints a customer id and customer code when none was sent', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id))
        ->assertStatus(201);

    $customer = Customer::where('id_number', '199012345678')->first();

    // Both are generated in the model's creating hook, and both are what the
    // rest of the system quotes back to a borrower.
    expect($customer->customer_id)->toStartWith('CUST-');
    expect($customer->customer_code)->toStartWith('CUS');
});

it('files the division details on the customer detail record', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
        'gn_division' => 'Kollupitiya',
        'ds_division' => 'Thimbirigasyaya',
        'district'    => 'Colombo',
        'province'    => 'Western',
    ]))->assertStatus(201);

    $customer = Customer::where('id_number', '199012345678')->first();

    expect($customer->customerDetail)->not->toBeNull();
    expect($customer->customerDetail->district)->toBe('Colombo');
});

it('does not create an empty detail record when no divisions were given', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id))
        ->assertStatus(201);

    expect(Customer::where('id_number', '199012345678')->first()->customerDetail)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

it('rejects an empty registration naming every required field', function () {
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), []),
        ['full_name', 'name_with_initials', 'id_type', 'id_number', 'address_line_1',
         'country', 'date_of_birth', 'phone_primary', 'have_whatsapp',
         'preferred_language', 'branch_id']
    );
});

it('rejects a second customer on the same id number', function () {
    // The NIC is the one identifier a person cannot hold twice, and the column
    // is unique, so catching it here is the difference between a 422 naming the
    // field and an unhandled database error.
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id))
        ->assertStatus(201);

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'full_name' => 'Someone Else',
        ])),
        ['id_number']
    );
});

it('rejects a date of birth in the future', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'date_of_birth' => now()->addYear()->toDateString(),
        ])),
        ['date_of_birth']
    );
});

it('rejects a branch that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload(999999)),
        ['branch_id']
    );
});

it('rejects a malformed email', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'email' => 'not-an-email',
        ])),
        ['email']
    );
});

it('rejects a portal password shorter than the policy everywhere else', function () {
    // Eight characters, the same as every other password this system takes. A
    // customer login created here signs in to the same application.
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'create_user_account' => true,
            'user_username'       => 'nimal',
            'user_password'       => 'short',
        ])),
        ['user_password']
    );
});

/*
|--------------------------------------------------------------------------
| The portal login
|--------------------------------------------------------------------------
*/

it('creates a portal login for every registered customer', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id))
        ->assertStatus(201);

    $customer = Customer::where('id_number', '199012345678')->first();
    $user = User::where('customer_id', $customer->id)->first();

    expect($user)->not->toBeNull();
    expect($user->user_type)->toBe('customer');
    expect((bool) $user->can_login)->toBeTrue();

    // Stamped deliberately: EnsurePasswordChanged holds any account whose
    // password nobody chose, and the portal has no change-password screen, so
    // a null here would lock every new customer out with no way back.
    expect($user->password_changed_at)->not->toBeNull();
});

it('names the login after the customer code when no username was given', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id))
        ->assertStatus(201);

    $customer = Customer::where('id_number', '199012345678')->first();

    expect(User::where('customer_id', $customer->id)->value('username'))->toBe($customer->customer_code);
});

it('uses the username the form supplied when one was given', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
        'create_user_account' => true,
        'user_username'       => 'nimal.perera',
        'user_password'       => 'secret12345',
    ]))->assertStatus(201);

    $customer = Customer::where('id_number', '199012345678')->first();

    expect(User::where('customer_id', $customer->id)->value('username'))->toBe('nimal.perera');
});

it('rejects a username another account already holds', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    User::factory()->create(['username' => 'taken.name']);

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'create_user_account' => true,
            'user_username'       => 'taken.name',
            'user_password'       => 'secret12345',
        ])),
        ['user_username']
    );
});

/*
|--------------------------------------------------------------------------
| Everything that rides in on the same payload
|--------------------------------------------------------------------------
*/

it('files bank details, assets, liabilities and guarantors against the customer', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
        'bank_details' => [
            ['bank_name' => 'Commercial Bank', 'account_number' => '1234567890'],
        ],
        'fixed_assets' => [
            ['owner_name' => 'Nimal Perera', 'property_location' => 'Galle', 'market_value' => 2500000],
        ],
        'moving_assets' => [
            ['assest_category' => 'vehicle', 'owner_name' => 'Nimal Perera', 'market_value' => 1800000],
        ],
        'liabilities' => [
            ['liability_type' => 'bank_loan', 'institution_name' => 'HNB', 'outstanding_balance' => 450000],
        ],
        'guarantors' => [
            ['full_name' => 'Sunil Silva', 'type' => 'guarantor_1', 'id_type' => 'nic', 'id_number' => '198511112222'],
        ],
    ]))->assertStatus(201);

    $customer = Customer::where('id_number', '199012345678')->first();

    expect($customer->bankDetails)->toHaveCount(1);
    expect($customer->fixedAssets)->toHaveCount(1);
    expect($customer->movingAssets)->toHaveCount(1);
    expect($customer->liabilities)->toHaveCount(1);
    expect($customer->guarantors)->toHaveCount(1);

    expect($customer->guarantors->first()->full_name)->toBe('Sunil Silva');
    expect((float) $customer->liabilities->first()->outstanding_balance)->toBe(450000.0);
});

it('rejects a guarantor with no name or id', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'guarantors' => [['type' => 'guarantor_1']],
        ])),
        ['guarantors.0.full_name', 'guarantors.0.id_number']
    );
});

it('rejects a liability of a type the business does not recognise', function () {
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
            'liabilities' => [
                ['liability_type' => 'loan_shark', 'institution_name' => 'X', 'outstanding_balance' => 1],
            ],
        ])),
        ['liabilities.0.liability_type']
    );
});

/*
|--------------------------------------------------------------------------
| All of it, or none of it
|--------------------------------------------------------------------------
*/

it('leaves nothing behind when the registration fails part way through', function () {
    // The write is a fan-out across seven tables. If the login cannot be minted
    // -- here because the username is already taken at the database level,
    // which the request rules do not see because it is applied after they run
    // -- then the customer, their assets and their guarantors must all go back
    // out with it. A customer row with no login cannot sign in and cannot be
    // registered again, because their NIC is now taken by the wreckage.
    $chain = $this->organisationChain();
    $this->actingAsApi($this->userWithPermissions(['Customer Create']));

    $customersBefore = Customer::count();

    // Occupy the username the customer_code fallback is about to want.
    $nextCode = 'CUS' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT);
    User::factory()->create(['username' => $nextCode]);

    $response = $this->postJson($this->api('/customers'), customerPayload($chain['branch']->id, [
        'guarantors' => [
            ['full_name' => 'Sunil Silva', 'type' => 'guarantor_1', 'id_type' => 'nic', 'id_number' => '198511112222'],
        ],
    ]));

    // The failure must be the one this test engineered -- the duplicate login
    // blowing up mid-transaction -- and not a 422 thrown before anything was
    // written, which would make the assertions below pass for free.
    expect($response->status())->toBe(500);

    // Nothing of the attempt survives.
    expect(Customer::count())->toBe($customersBefore);
    $this->assertDatabaseMissing('customers', ['id_number' => '199012345678']);
    $this->assertDatabaseMissing('guarantors', ['id_number' => '198511112222']);
});
