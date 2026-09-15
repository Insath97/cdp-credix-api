<?php

use App\Jobs\SendSmsJob;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\PasswordChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Access control: passwords
|--------------------------------------------------------------------------
|
| Three ways a password can change, and they trust three different things.
| The signed-in route trusts the old password. The OTP routes trust a code
| sent to the phone on file, which is the only thing left when the old
| password is the thing that was lost. The rules that separate them are the
| whole of the account-recovery attack surface.
|
*/

/**
 * A staff account with an employee record carrying a primary phone.
 *
 * The OTP routes resolve the recipient number from the employee row for
 * anything that is not a customer, so a back-office user with no employee can
 * never be sent a code at all. Every OTP test below therefore needs this
 * shape, not the plain admin the factory builds by default.
 */
function passwordTestStaffUser(string $marker, array $userAttributes = []): User
{
    $employee = Employee::create([
        'f_name'             => 'Nimal',
        'l_name'             => 'Perera',
        'full_name'          => 'Nimal Perera',
        'name_with_initials' => 'N. Perera',
        'employee_code'      => 'EMP-' . $marker,
        'id_number'          => 'NIC-' . $marker,
        'phone_primary'      => '0771234567',
        'is_active'          => true,
    ]);

    $user = User::factory()->create(array_merge([
        'user_type'   => 'staff',
        'employee_id' => $employee->id,
    ], $userAttributes));

    // canLogin() and the back-office login both insist on a role, so the
    // "and then the new password works" half of these tests needs one.
    $user->assignRole(Role::findOrCreate('Recovery Officer', 'api'));

    return $user->fresh();
}

/*
|--------------------------------------------------------------------------
| Change with the current password
|--------------------------------------------------------------------------
*/

it('requires authentication to change a password', function () {
    $this->postJson($this->api('/password/change'), [])->assertStatus(401);
});

it('changes a password when the current one is given correctly', function () {
    $user = $this->superAdmin();

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/change'), [
        'current_password'      => 'password',
        'password'              => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ])->assertStatus(200)
        ->assertJsonPath('status', 'success');

    // The real proof is not the 200 but that the new secret opens the door and
    // the old one no longer does.
    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'brand-new-secret',
    ])->assertStatus(200);

    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ])->assertStatus(401);
});

it('stamps password_changed_at so the hold is lifted', function () {
    $user = $this->userWithPermissions([], ['password_changed_at' => null]);

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/change'), [
        'current_password'      => 'password',
        'password'              => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ])->assertStatus(200);

    expect($user->fresh()->password_changed_at)->not->toBeNull();
});

it('refuses a change when the current password is wrong', function () {
    $user = $this->superAdmin();

    $this->actingAsApi($user);

    $response = $this->postJson($this->api('/password/change'), [
        'current_password'      => 'not-my-password',
        'password'              => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ]);

    $this->assertValidationFailed($response, ['current_password']);

    // And nothing moved: the old password still works.
    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ])->assertStatus(200);
});

it('refuses a change whose confirmation does not match', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->assertValidationFailed(
        $this->postJson($this->api('/password/change'), [
            'current_password'      => 'password',
            'password'              => 'brand-new-secret',
            'password_confirmation' => 'something-else-entirely',
        ]),
        ['password']
    );
});

it('refuses a new password identical to the current one', function () {
    // Otherwise "change your password" is satisfied by typing the same thing
    // twice, and the hold on a never-changed account is lifted without a
    // single character of the credential having moved.
    $this->actingAsApi($this->userWithPermissions([]));

    $this->assertValidationFailed(
        $this->postJson($this->api('/password/change'), [
            'current_password'      => 'password',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]),
        ['password']
    );
});

it('refuses a new password shorter than the minimum', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->assertValidationFailed(
        $this->postJson($this->api('/password/change'), [
            'current_password'      => 'password',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]),
        ['password']
    );
});

/*
|--------------------------------------------------------------------------
| Forgot password
|--------------------------------------------------------------------------
*/

it('issues a reset OTP for a known user', function () {
    // The send is a queued job, faked so the suite never reaches the SMS
    // gateway. What matters here is the record written alongside it.
    Queue::fake();

    $user = passwordTestStaffUser('forgot-known');

    $this->postJson($this->api('/forgot-password'), [
        'username' => $user->username,
    ])->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('password_change_requests', [
        'user_id' => $user->id,
        'status'  => 'approved',
    ]);

    Queue::assertPushed(SendSmsJob::class);
});

it('answers forgot-password for an unknown username with a 404', function () {
    Queue::fake();

    $response = $this->postJson($this->api('/forgot-password'), [
        'username' => 'nobody-by-that-name',
    ]);

    // Worth stating plainly, because it is a user-enumeration oracle: the
    // endpoint distinguishes "no such account" (404) from "code sent" (200),
    // so anyone can confirm whether a username exists without a password. The
    // login endpoint next door is careful not to do this. This test records
    // what the controller actually does today rather than what it should.
    $response->assertStatus(404);
    $response->assertJsonPath('message', 'User not found.');

    Queue::assertNotPushed(SendSmsJob::class);
});

it('rejects a forgot-password request with no username', function () {
    $this->assertValidationFailed(
        $this->postJson($this->api('/forgot-password'), []),
        ['username']
    );
});

it('refuses a second reset OTP while the first is still live', function () {
    Queue::fake();

    $user = passwordTestStaffUser('forgot-twice');

    $this->postJson($this->api('/forgot-password'), ['username' => $user->username])
        ->assertStatus(200);

    $this->postJson($this->api('/forgot-password'), ['username' => $user->username])
        ->assertStatus(400)
        ->assertJsonPath('message', 'You already have a pending password change request.');
});

it('cannot send a reset OTP to a user with no phone on file', function () {
    Queue::fake();

    // A plain admin account has no employee row, and the recipient number for
    // anything that is not a customer is read off the employee. So no phone,
    // no reset -- which is worth stating, because it means a back-office admin
    // account can never recover itself through this endpoint.
    $user = $this->userWithPermissions([]);

    $this->postJson($this->api('/forgot-password'), ['username' => $user->username])
        ->assertStatus(400)
        ->assertJsonPath('message', 'Primary phone number not found. Cannot request password change.');
});

/*
|--------------------------------------------------------------------------
| Reset with the forgot-password OTP
|--------------------------------------------------------------------------
*/

it('resets a password with a valid OTP', function () {
    Queue::fake();

    $user = passwordTestStaffUser('reset-ok');

    $this->postJson($this->api('/forgot-password'), ['username' => $user->username])
        ->assertStatus(200);

    $otp = PasswordChangeRequest::where('user_id', $user->id)->latest()->first();

    $this->postJson($this->api('/reset-forgot-password'), [
        'username'              => $user->username,
        'otp'                   => $otp->otp,
        'password'              => 'recovered-secret',
        'password_confirmation' => 'recovered-secret',
    ])->assertStatus(200)
        ->assertJsonPath('status', 'success');

    expect($otp->fresh()->status)->toBe('verified');

    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'recovered-secret',
    ])->assertStatus(200);
});

it('refuses a reset with an OTP that does not match', function () {
    Queue::fake();

    $user = passwordTestStaffUser('reset-bad-otp');

    $this->postJson($this->api('/forgot-password'), ['username' => $user->username])
        ->assertStatus(200);

    $otp = PasswordChangeRequest::where('user_id', $user->id)->latest()->first();
    $wrong = str_pad((string) ((((int) $otp->otp) + 1) % 1000000), 6, '0', STR_PAD_LEFT);

    $this->postJson($this->api('/reset-forgot-password'), [
        'username'              => $user->username,
        'otp'                   => $wrong,
        'password'              => 'recovered-secret',
        'password_confirmation' => 'recovered-secret',
    ])->assertStatus(400)
        ->assertJsonPath('message', 'Invalid OTP or no approved request found.');

    // And the old password is untouched.
    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'password',
    ])->assertStatus(200);
});

it('refuses a reset with an expired OTP', function () {
    Queue::fake();

    $user = passwordTestStaffUser('reset-expired');

    $this->postJson($this->api('/forgot-password'), ['username' => $user->username])
        ->assertStatus(200);

    $otp = PasswordChangeRequest::where('user_id', $user->id)->latest()->first();

    // Age the record rather than moving the clock: travelling time would also
    // move the JWT issued-at claims the rest of the request depends on.
    $otp->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->postJson($this->api('/reset-forgot-password'), [
        'username'              => $user->username,
        'otp'                   => $otp->otp,
        'password'              => 'recovered-secret',
        'password_confirmation' => 'recovered-secret',
    ])->assertStatus(400)
        ->assertJsonPath('message', 'OTP has expired.');
});

it('refuses a reset for an unknown username', function () {
    $this->postJson($this->api('/reset-forgot-password'), [
        'username'              => 'nobody-by-that-name',
        'otp'                   => '123456',
        'password'              => 'recovered-secret',
        'password_confirmation' => 'recovered-secret',
    ])->assertStatus(404)
        ->assertJsonPath('message', 'User not found.');
});

it('will not accept another user OTP', function () {
    // The record is looked up by user AND code, so a code issued to one
    // account must be useless against another. Without that pairing, a single
    // leaked six-digit number would reset whichever account the attacker named.
    Queue::fake();

    $victim   = passwordTestStaffUser('reset-victim');
    $attacker = passwordTestStaffUser('reset-attacker');

    $this->postJson($this->api('/forgot-password'), ['username' => $attacker->username])
        ->assertStatus(200);

    $otp = PasswordChangeRequest::where('user_id', $attacker->id)->latest()->first();

    $this->postJson($this->api('/reset-forgot-password'), [
        'username'              => $victim->username,
        'otp'                   => $otp->otp,
        'password'              => 'stolen-account',
        'password_confirmation' => 'stolen-account',
    ])->assertStatus(400);
});

it('rejects a reset whose OTP is not six digits', function () {
    $this->assertValidationFailed(
        $this->postJson($this->api('/reset-forgot-password'), [
            'username'              => 'someone',
            'otp'                   => '12',
            'password'              => 'recovered-secret',
            'password_confirmation' => 'recovered-secret',
        ]),
        ['otp']
    );
});

/*
|--------------------------------------------------------------------------
| Change with an OTP while signed in
|--------------------------------------------------------------------------
*/

it('changes a password with an OTP requested while signed in', function () {
    Queue::fake();

    $user = passwordTestStaffUser('otp-change');

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/request-change'))
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $otp = PasswordChangeRequest::where('user_id', $user->id)->latest()->first();

    $this->postJson($this->api('/password/change-with-otp'), [
        'otp'                   => $otp->otp,
        'password'              => 'otp-chosen-secret',
        'password_confirmation' => 'otp-chosen-secret',
    ])->assertStatus(200);

    expect($otp->fresh()->status)->toBe('verified');
    expect($user->fresh()->password_changed_at)->not->toBeNull();

    $this->postJson($this->api('/login'), [
        'login'    => $user->username,
        'password' => 'otp-chosen-secret',
    ])->assertStatus(200);
});

it('refuses a signed-in OTP change with the wrong code', function () {
    Queue::fake();

    $user = passwordTestStaffUser('otp-change-bad');

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/request-change'))->assertStatus(200);

    $otp = PasswordChangeRequest::where('user_id', $user->id)->latest()->first();
    $wrong = str_pad((string) ((((int) $otp->otp) + 1) % 1000000), 6, '0', STR_PAD_LEFT);

    $this->postJson($this->api('/password/change-with-otp'), [
        'otp'                   => $wrong,
        'password'              => 'otp-chosen-secret',
        'password_confirmation' => 'otp-chosen-secret',
    ])->assertStatus(400)
        ->assertJsonPath('message', 'Invalid OTP or no approved request found.');
});

it('refuses a signed-in OTP change once the code has expired', function () {
    Queue::fake();

    $user = passwordTestStaffUser('otp-change-expired');

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/request-change'))->assertStatus(200);

    $otp = PasswordChangeRequest::where('user_id', $user->id)->latest()->first();
    $otp->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->postJson($this->api('/password/change-with-otp'), [
        'otp'                   => $otp->otp,
        'password'              => 'otp-chosen-secret',
        'password_confirmation' => 'otp-chosen-secret',
    ])->assertStatus(400)
        ->assertJsonPath('message', 'OTP has expired.');
});

it('cannot request a change OTP without a phone on file', function () {
    Queue::fake();

    $this->actingAsApi($this->userWithPermissions([]));

    $this->postJson($this->api('/password/request-change'))
        ->assertStatus(400)
        ->assertJsonPath('message', 'Primary phone number not found. Cannot send OTP.');
});

it('sends a customer change OTP to the customer phone, not an employee one', function () {
    Queue::fake();

    $customer = Customer::create([
        'full_name'     => 'Sunil Fernando',
        'phone_primary' => '0779876543',
    ]);

    $user = $this->customerUser(['customer_id' => $customer->id]);

    $this->actingAsApi($user);

    $this->postJson($this->api('/password/request-change'))->assertStatus(200);

    Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) {
        return $job->numbers === '0779876543';
    });
});
