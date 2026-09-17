<?php

use App\Http\Controllers\V1\AuthController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * The login endpoint answers in constant wording and never says whether a
 * username exists, which is what makes it worth guessing against: an attacker
 * learns nothing from one answer, so they ask for thousands. These tests pin
 * the limit that stops them, and -- just as important -- pin the cases that
 * must NOT count against a real officer having a bad morning.
 */

function throttledUser(array $attributes = []): User
{
    $user = User::factory()->create(array_merge([
        'username'            => 'kamala',
        'user_type'           => 'admin',
        'is_active'           => true,
        'can_login'           => true,
        'password'            => Hash::make('correct-horse'),
        'password_changed_at' => now(),
    ], $attributes));

    // A back-office user with no role is refused at the door with a 403, well
    // before a token is issued. These tests need the success path to actually
    // succeed, so the account has to carry one.
    $user->assignRole(Role::findOrCreate('Branch Officer', 'api'));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

function failLogin(string $login = 'kamala', string $password = 'wrong-guess')
{
    return test()->postJson('/api/v1/login', [
        'login'    => $login,
        'password' => $password,
    ]);
}

it('refuses further attempts once the failure limit is reached', function () {
    throttledUser();

    for ($i = 1; $i <= AuthController::LOGIN_MAX_ATTEMPTS; $i++) {
        failLogin()->assertStatus(401);
    }

    $blocked = failLogin();

    $blocked->assertStatus(429);
    $blocked->assertHeader('Retry-After');
    expect($blocked->json('retry_after'))->toBeGreaterThan(0);
});

it('refuses the correct password too once the limit is reached', function () {
    throttledUser();

    for ($i = 1; $i <= AuthController::LOGIN_MAX_ATTEMPTS; $i++) {
        failLogin()->assertStatus(401);
    }

    // The whole point: the lock cannot be stepped over by finally guessing
    // right on the next attempt.
    failLogin('kamala', 'correct-horse')->assertStatus(429);
});

it('clears the counter when the password is finally correct', function () {
    throttledUser();

    for ($i = 1; $i < AuthController::LOGIN_MAX_ATTEMPTS; $i++) {
        failLogin()->assertStatus(401);
    }

    $this->postJson('/api/v1/login', [
        'login'    => 'kamala',
        'password' => 'correct-horse',
    ])->assertStatus(200);

    // One more wrong password must be an ordinary 401, not the 429 it would
    // have been had the earlier failures still been standing.
    failLogin()->assertStatus(401);
});

it('counts each account separately so one officer cannot lock out the branch', function () {
    throttledUser();
    throttledUser(['username' => 'suresh', 'email' => 'suresh@example.test']);

    // Kamala exhausts her allowance from this address.
    for ($i = 1; $i <= AuthController::LOGIN_MAX_ATTEMPTS; $i++) {
        failLogin()->assertStatus(401);
    }
    failLogin()->assertStatus(429);

    // Suresh, on the same NAT address, is untouched by it.
    $this->postJson('/api/v1/login', [
        'login'    => 'suresh',
        'password' => 'correct-horse',
    ])->assertStatus(200);
});

it('does not burn an attempt when the password was right but the account is deactivated', function () {
    throttledUser(['is_active' => false]);

    // Six tries with the correct password: the refusal is a fact about the
    // account, not a guess at it, so it must never accumulate.
    for ($i = 1; $i <= AuthController::LOGIN_MAX_ATTEMPTS + 1; $i++) {
        $this->postJson('/api/v1/login', [
            'login'    => 'kamala',
            'password' => 'correct-horse',
        ])->assertStatus(401)->assertJson(['message' => 'Account is deactivated']);
    }
});

it('does not let capitalisation buy a fresh allowance', function () {
    throttledUser();

    foreach (['kamala', 'KAMALA', 'Kamala', 'kAmAlA', 'KaMaLa'] as $spelling) {
        failLogin($spelling)->assertStatus(401);
    }

    failLogin('KAMALA')->assertStatus(429);
});

it('throttles the email alias on the same counter as the login field', function () {
    throttledUser(['email' => 'kamala@example.test']);

    // The controller merges `email` into `login`, and the throttle key is
    // built before that merge -- so it has to read both itself.
    for ($i = 1; $i <= AuthController::LOGIN_MAX_ATTEMPTS; $i++) {
        $this->postJson('/api/v1/login', [
            'email'    => 'kamala@example.test',
            'password' => 'wrong-guess',
        ])->assertStatus(401);
    }

    $this->postJson('/api/v1/login', [
        'email'    => 'kamala@example.test',
        'password' => 'wrong-guess',
    ])->assertStatus(429);
});

it('still rejects a malformed request with a validation error, not a throttle', function () {
    throttledUser();

    // Validation runs before the throttle check, so a caller who forgets the
    // password field is told so rather than being counted as a guesser.
    for ($i = 1; $i <= AuthController::LOGIN_MAX_ATTEMPTS + 1; $i++) {
        $this->postJson('/api/v1/login', ['login' => 'kamala'])->assertStatus(422);
    }
});
