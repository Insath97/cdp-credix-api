<?php

use App\Http\Controllers\V1\AuthController;
use App\Models\Customer;
use App\Models\LoginOtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Access control: authentication
|--------------------------------------------------------------------------
|
| The front door. Everything else in this suite assumes a token, so the rules
| enforced here -- who may exchange a password for one, what that token is
| still allowed to reach, and when it stops working -- decide the blast radius
| of every other endpoint.
|
*/

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

it('issues a token for a correct username and password', function () {
    // A back-office login is refused outright unless the account holds at
    // least one role, so the happy path has to be a user who has one.
    $user = $this->superAdmin();

    $response = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    $response->assertJsonPath('data.token_type', 'bearer');
    $response->assertJsonPath('data.user.id', $user->id);

    expect($response->json('data.auth_token'))->toBeString()->not->toBeEmpty();

    // The login trail is written as part of the exchange, not lazily later.
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('accepts the email field as an alias for login', function () {
    // The controller merges `email` into `login` when only the former is
    // present, because the sign-in form has shipped both names over its life
    // and neither one may be allowed to break.
    $user = $this->superAdmin();

    $response = $this->postJson($this->api('/login'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.user.id', $user->id);
});

it('resolves a login by email address as well as by username', function () {
    $user = $this->superAdmin();

    $this->postJson($this->api('/login'), [
        'login'    => $user->email,
        'password' => 'password',
    ])->assertStatus(200);
});

it('answers a wrong password and an unknown user identically', function () {
    $user = $this->superAdmin();

    $wrongPassword = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'not-the-password',
    ]);

    $unknownUser = $this->postJson($this->api('/login'), [
        'login'    => 'nobody-by-that-name',
        'password' => 'password',
    ]);

    $wrongPassword->assertStatus(401);
    $unknownUser->assertStatus(401);

    // Deliberately the same wording for both. A login that says "no such user"
    // for one and "wrong password" for the other is a free username oracle:
    // anyone can enumerate who holds an account without ever guessing a
    // password.
    expect($wrongPassword->json('message'))->toBe($unknownUser->json('message'));
    expect($wrongPassword->json('message'))->toBe('Invalid credentials');
});

it('requires both a login and a password', function () {
    // This endpoint validates by hand rather than through a form request, so
    // it answers in Laravel's own field => messages shape.
    $this->assertValidationFailed(
        $this->postJson($this->api('/login'), []),
        ['login', 'password']
    );
});

it('refuses a deactivated account', function () {
    $user = User::factory()->inactive()->create();
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('Manager', 'api'));

    $response = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $response->assertStatus(401);
    $response->assertJsonPath('message', 'Account is deactivated');
});

it('refuses a back-office user who holds no role at all', function () {
    // An account with permissions but no role is what a half-finished user
    // creation leaves behind. The controller stops it at login rather than
    // letting it in to meet a wall of 403s it cannot interpret.
    $user = $this->userWithPermissions(['User Index']);

    $response = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $response->assertStatus(403);
    $response->assertJsonPath('message', 'No admin role assigned. Please contact Super Admin.');
});

it('lets a customer in without a role, because the role check is back-office only', function () {
    // customerUser() is already two-factor verified, so this is the returning
    // customer path: straight to a token, no OTP, no role required.
    $user = $this->customerUser();

    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ])->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| First-login OTP (customers)
|--------------------------------------------------------------------------
*/

it('sends an OTP instead of a token on a customer first login', function () {
    // The SMS gateway is a real outbound HTTP call made inline by the
    // controller, so it is faked here -- otherwise the test spends the
    // gateway's connect timeout and its result depends on the network.
    Http::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0771234567',
    ]);

    $user = $this->customerUser([
        'customer_id'            => $customer->id,
        'two_factor_verified_at' => null,
    ]);

    $response = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.otp_required', true);

    // No token is handed out until the code comes back.
    expect($response->json('data.auth_token'))->toBeNull();
    expect($response->json('data.reference'))->toBeString();

    $this->assertDatabaseHas('login_otp_verifications', [
        'user_id'   => $user->id,
        'reference' => $response->json('data.reference'),
        'status'    => 'approved',
    ]);
});

it('tells the caller how long the login OTP really lasts', function () {
    // The countdown the customer watches is driven by expires_in. If it does
    // not match the window the record was actually written with, the screen
    // sits there inviting a code that stopped working long ago.
    Http::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0771234567',
    ]);

    $user = $this->customerUser([
        'customer_id'            => $customer->id,
        'two_factor_verified_at' => null,
    ]);

    $response = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $response->assertStatus(200);

    expect($response->json('data.expires_in'))
        ->toBe(AuthController::LOGIN_OTP_TTL_SECONDS);
});

it('exchanges a valid login OTP for a token', function () {
    Http::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0771234567',
    ]);

    $user = $this->customerUser([
        'customer_id'            => $customer->id,
        'two_factor_verified_at' => null,
    ]);

    $login = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $record = LoginOtpVerification::where('user_id', $user->id)->latest()->first();

    $response = $this->postJson($this->api('/login/verify-otp'), [
        'reference' => $login->json('data.reference'),
        'otp'       => $record->otp,
    ]);

    $response->assertStatus(200);
    expect($response->json('data.auth_token'))->toBeString()->not->toBeEmpty();

    // The code is spent: it is marked verified so it cannot be replayed.
    expect($record->fresh()->status)->toBe('verified');
});

it('remembers that a customer has passed two-factor', function () {
    // Verified once, never asked again. The flag is what makes a customer's
    // second login go straight to a token; without it every single login
    // starts another SMS and another 60-second wait, forever.
    Http::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0771234567',
    ]);

    $user = $this->customerUser([
        'customer_id'            => $customer->id,
        'two_factor_verified_at' => null,
    ]);

    $login = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $record = LoginOtpVerification::where('user_id', $user->id)->latest()->first();

    $this->postJson($this->api('/login/verify-otp'), [
        'reference' => $login->json('data.reference'),
        'otp'       => $record->otp,
    ])->assertStatus(200);

    $this->assertNotNull(
        $user->fresh()->two_factor_verified_at,
        'AuthController::verifyOtp() writes two_factor_verified_at through '
        . '$user->update(), but the column is missing from User::$fillable, so '
        . 'mass assignment silently drops it. The customer is therefore never '
        . 'recorded as verified and is sent a fresh OTP on every login.'
    );

    // Which is the consequence worth stating: the second login must hand over
    // a token, not start the whole OTP dance again.
    $second = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    expect($second->json('data.auth_token'))->toBeString()->not->toBeEmpty();
});

it('refuses a login OTP that does not match', function () {
    Http::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0771234567',
    ]);

    $user = $this->customerUser([
        'customer_id'            => $customer->id,
        'two_factor_verified_at' => null,
    ]);

    $login = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $record = LoginOtpVerification::where('user_id', $user->id)->latest()->first();

    // A six-digit code that is definitely not the one on file.
    $wrong = str_pad((string) ((((int) $record->otp) + 1) % 1000000), 6, '0', STR_PAD_LEFT);

    $this->postJson($this->api('/login/verify-otp'), [
        'reference' => $login->json('data.reference'),
        'otp'       => $wrong,
    ])->assertStatus(400)
        ->assertJsonPath('message', 'Invalid OTP or no pending login verification found.');
});

it('refuses a login OTP that has expired', function () {
    Http::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0771234567',
    ]);

    $user = $this->customerUser([
        'customer_id'            => $customer->id,
        'two_factor_verified_at' => null,
    ]);

    $login = $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ]);

    $record = LoginOtpVerification::where('user_id', $user->id)->latest()->first();

    // Aged past the window rather than travelling the clock, so the rest of
    // the request (JWT issue-time claims included) stays in the present.
    $record->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->postJson($this->api('/login/verify-otp'), [
        'reference' => $login->json('data.reference'),
        'otp'       => $record->otp,
    ])->assertStatus(400)
        ->assertJsonPath('message', 'OTP has expired.');
});

/*
|--------------------------------------------------------------------------
| Identity, logout and the profile screen
|--------------------------------------------------------------------------
*/

it('requires a token for the identity endpoint', function () {
    $this->getJson($this->api('/me'))->assertStatus(401);
});

it('returns the signed-in user from /me', function () {
    $user = $this->userWithPermissions(['User Index']);

    $this->actingAsApi($user);

    $response = $this->getJson($this->api('/me'));

    $response->assertStatus(200);
    $response->assertJsonPath('data.user.id', $user->id);
    $response->assertJsonPath('data.user.username', $user->username);

    // The password hash must never travel, whatever else the payload grows.
    expect($response->json('data.user'))->not->toHaveKey('password');
});

it('invalidates the token on logout', function () {
    $user = $this->userWithPermissions(['User Index']);

    $this->actingAsApi($user);

    $this->postJson($this->api('/logout'))->assertStatus(200);

    // withToken() keeps sending the same header, which is the point: the token
    // string itself must stop working, not merely be dropped by the client.
    $this->getJson($this->api('/me'))->assertStatus(401);
});

it('updates the signed-in user profile over PUT', function () {
    $user = $this->userWithPermissions([]);

    $this->actingAsApi($user);

    $response = $this->putJson($this->api('/me'), [
        'name'  => 'Renamed Over Put',
        'email' => 'put@example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.user.name', 'Renamed Over Put');

    $this->assertDatabaseHas('users', [
        'id'    => $user->id,
        'name'  => 'Renamed Over Put',
        'email' => 'put@example.com',
    ]);
});

it('updates the signed-in user profile over PATCH', function () {
    // Both verbs are bound to the same action. The profile screen sends a
    // partial body, so PATCH is the honest verb, but PUT is what it has always
    // sent and dropping it would break the deployed frontend.
    $user = $this->userWithPermissions([]);

    $this->actingAsApi($user);

    $this->patchJson($this->api('/me'), ['name' => 'Renamed Over Patch'])
        ->assertStatus(200)
        ->assertJsonPath('data.user.name', 'Renamed Over Patch');

    $this->assertDatabaseHas('users', [
        'id'   => $user->id,
        'name' => 'Renamed Over Patch',
    ]);
});

it('lets a profile update resend the user own email address', function () {
    $user = $this->userWithPermissions([]);

    $this->actingAsApi($user);

    // The form posts the whole record back, so the unchanged email must not
    // read as a duplicate of itself.
    $this->patchJson($this->api('/me'), [
        'name'  => 'Same Email',
        'email' => $user->email,
    ])->assertStatus(200);
});

it('refuses a profile update that takes another user email address', function () {
    $other = $this->userWithPermissions([]);
    $user  = $this->userWithPermissions([]);

    $this->actingAsApi($user);

    $this->assertValidationFailed(
        $this->patchJson($this->api('/me'), ['email' => $other->email]),
        ['email']
    );
});

it('does not let the profile endpoint reach anything but name and email', function () {
    // updateProfile() validates exactly two keys and fills only those. Anything
    // else in the body is a user trying to promote themselves through the one
    // endpoint every signed-in account can reach.
    $user = $this->userWithPermissions([], ['is_active' => true]);

    $this->actingAsApi($user);

    $this->patchJson($this->api('/me'), [
        'name'       => 'Still Ordinary',
        'user_type'  => 'admin',
        'is_active'  => false,
        'can_login'  => false,
    ])->assertStatus(200);

    $fresh = $user->fresh();

    expect($fresh->is_active)->toBeTrue();
    expect($fresh->can_login)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| EnsurePasswordChanged
|--------------------------------------------------------------------------
|
| An account whose password was chosen by somebody else is held at the door
| until it sets one of its own. The allow list is deliberately narrow: the
| change itself, the identity call the frontend makes on boot, and the way
| out. Getting this wrong in either direction is serious -- too tight and a
| blocked user cannot even reach the dialog that unblocks them, too loose and
| the hold is decoration.
|
*/

it('holds a user who has never set a password out of an ordinary endpoint', function () {
    // Country Index is granted on purpose. Without it a 403 would prove
    // nothing, because the permission middleware would have produced one
    // anyway and the test would pass for the wrong reason.
    $user = $this->userWithPermissions(['Country Index'], ['password_changed_at' => null]);

    $this->actingAsApi($user);

    $response = $this->getJson($this->api('/countries'));

    $response->assertStatus(403);
    $response->assertJsonPath('message', 'Set your own password before using the system.');
    $response->assertJsonPath('errors.password_change_required', true);
});

it('still lets a held user reach /me, which is how the frontend learns it is held', function () {
    $user = $this->userWithPermissions([], ['password_changed_at' => null]);

    $this->actingAsApi($user);

    $response = $this->getJson($this->api('/me'));

    $response->assertStatus(200);
    $response->assertJsonPath('data.user.id', $user->id);

    // The timestamp itself stays hidden -- when someone last changed their
    // password is nobody else's business -- but the yes/no has to be readable
    // or the frontend cannot route them to the change-password dialog.
    $response->assertJsonPath('data.user.password_change_required', true);
});

it('still lets a held user log out', function () {
    $user = $this->userWithPermissions([], ['password_changed_at' => null]);

    $this->actingAsApi($user);

    $this->postJson($this->api('/logout'))->assertStatus(200);
});

it('lets a held user reach the password change endpoints', function () {
    $user = $this->userWithPermissions([], ['password_changed_at' => null]);

    $this->actingAsApi($user);

    // Not asserting success here, only that the middleware let the request
    // through to the controller: a 422 or 400 is the controller talking, a 403
    // would be the door still shut.
    $response = $this->postJson($this->api('/password/change'), []);

    expect($response->status())->not->toBe(403);
});

it('lets a user through once they have set their own password', function () {
    $user = $this->userWithPermissions(['Country Index'], ['password_changed_at' => null]);

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/change'), [
        'current_password'      => 'password',
        'password'              => 'a-password-of-my-own',
        'password_confirmation' => 'a-password-of-my-own',
    ])->assertStatus(200);

    // Same token, same user, and now the door is open.
    $this->getJson($this->api('/countries'))->assertStatus(200);
});
