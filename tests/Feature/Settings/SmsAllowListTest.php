<?php

use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The system is being exercised against real customer records, so an overdue
 * reminder or a recovery escalation aimed at a borrower's own number would
 * reach that borrower. These tests pin the stop: two permitted handsets, and
 * no way for any caller to address a third.
 */

beforeEach(function () {
    config()->set('services.dialog_sms.allowed_numbers', ['0752932640', '0744125923']);

    // getAccessToken() caches its token; seeding it keeps these tests about
    // the allow-list rather than about the login handshake.
    Cache::put('dialog_sms_token', 'test-token', now()->addHour());
});

/** The msisdn list the gateway was actually asked to deliver to. */
function sentTo(): array
{
    $numbers = [];

    foreach (Http::recorded() as [$request, $response]) {
        foreach ($request->data()['msisdn'] ?? [] as $entry) {
            $numbers[] = $entry['mobile'];
        }
    }

    return $numbers;
}

function sentMessage(): ?string
{
    foreach (Http::recorded() as [$request, $response]) {
        if (isset($request->data()['message'])) {
            return $request->data()['message'];
        }
    }

    return null;
}

function fakeGateway(): void
{
    Http::fake([
        '*' => Http::response(['status' => 'success', 'data' => ['campaignId' => 1]], 200),
    ]);
}

it('never addresses a customer number, redirecting to the permitted handsets instead', function () {
    fakeGateway();

    app(SmsService::class)->sendSms('0761112223', 'Your loan is overdue.');

    // The customer's own handset must not appear anywhere in the payload.
    expect(sentTo())->not->toContain('761112223');
    expect(sentTo())->toEqualCanonicalizing(['752932640', '744125923']);
});

it('writes the intended recipient into the redirected message', function () {
    fakeGateway();

    app(SmsService::class)->sendSms('0761112223', 'Your loan is overdue.');

    // Otherwise a tester reading the two handsets cannot tell which borrower
    // each text was really about.
    expect(sentMessage())->toContain('761112223');
    expect(sentMessage())->toContain('Your loan is overdue.');
});

it('leaves a permitted number alone and does not tag its message', function () {
    fakeGateway();

    app(SmsService::class)->sendSms('0752932640', 'Your OTP is 998877.');

    expect(sentTo())->toEqualCanonicalizing(['752932640']);
    expect(sentMessage())->toBe('Your OTP is 998877.');
});

it('recognises a permitted number however it is written', function (string $spelling) {
    fakeGateway();

    app(SmsService::class)->sendSms($spelling, 'Your OTP is 998877.');

    // Not redirected, so the number was recognised as permitted.
    expect(sentTo())->toEqualCanonicalizing(['752932640']);
    expect(sentMessage())->toBe('Your OTP is 998877.');
})->with([
    '0752932640',
    '752932640',
    '94752932640',
    '+94752932640',
    '075 293 2640',
    '075-293-2640',
]);

it('strips a customer out of a mixed batch without dropping the permitted one', function () {
    fakeGateway();

    app(SmsService::class)->sendSms(['0752932640', '0761112223'], 'Group loan disbursed.');

    expect(sentTo())->not->toContain('761112223');
    // The permitted number appears once, not twice, despite being both an
    // original recipient and a redirect target.
    expect(sentTo())->toEqualCanonicalizing(['752932640', '744125923']);
});

it('addresses every recipient normally when the list is empty, as on the live server', function () {
    config()->set('services.dialog_sms.allowed_numbers', []);
    fakeGateway();

    app(SmsService::class)->sendSms('0761112223', 'Your loan is overdue.');

    expect(sentTo())->toEqualCanonicalizing(['761112223']);
    expect(sentMessage())->toBe('Your loan is overdue.');
});

it('holds the login OTP to the permitted handsets as well', function () {
    fakeGateway();

    // The OTP path calls SmsService directly rather than through
    // NotificationService, so it is worth pinning separately: a customer
    // must not be able to have a code sent to their own phone in testing.
    app(SmsService::class)->sendSms('0761112223', 'CDP Credix: Your OTP for login is 998877.');

    expect(sentTo())->not->toContain('761112223');
    expect(sentTo())->toEqualCanonicalizing(['752932640', '744125923']);
});

it('does not put the message body in the log when it redirects', function () {
    fakeGateway();

    // The redirect warning names the numbers only. An OTP or an arrears
    // figure in the log would be a standing leak.
    $redirectEntries = [];

    \Illuminate\Support\Facades\Log::listen(function ($entry) use (&$redirectEntries) {
        if ($entry->message === 'SMS redirected to the allow-list') {
            $redirectEntries[] = json_encode($entry->context);
        }
    });

    app(SmsService::class)->sendSms('0761112223', 'Your OTP is 998877.');

    expect($redirectEntries)->toHaveCount(1);

    // It names where the text was headed and where it went, and carries no
    // part of the body with it.
    expect($redirectEntries[0])->toContain('761112223');
    expect($redirectEntries[0])->toContain('752932640');
    expect($redirectEntries[0])->not->toContain('998877');
    expect($redirectEntries[0])->not->toContain('OTP');
});
