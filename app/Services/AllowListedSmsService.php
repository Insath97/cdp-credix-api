<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Holds every outgoing SMS to a fixed list of handsets.
 *
 * The system is being exercised against real customer records, so an overdue
 * reminder, a recovery escalation or a login OTP aimed at a borrower's own
 * number would reach that borrower. This class is the stop.
 *
 * It sits in front of SmsService rather than inside it: AppServiceProvider
 * binds SmsService::class to this subclass, so NotificationService, SendSmsJob,
 * the scheduled reminder commands and the login OTP path all pass through here
 * without any of them -- or SmsService itself -- being modified. Nothing in the
 * application constructs SmsService with `new`, so there is no way around it.
 *
 * GOING LIVE: set SMS_ALLOWED_NUMBERS= (empty) in the production .env. An
 * empty list means no restriction and every recipient is addressed normally.
 * Leaving the test numbers in place on a live server would quietly send every
 * customer's SMS to two staff phones instead of to the customer.
 */
class AllowListedSmsService extends SmsService
{
    /**
     * @param  string|array  $numbers
     */
    public function sendSms($numbers, string $message, int $paymentMethod = 0): bool
    {
        [$recipients, $message] = $this->applyAllowList(
            is_array($numbers) ? $numbers : [$numbers],
            $message
        );

        if ($recipients === []) {
            Log::warning('SMS not sent: no permitted recipient');

            return false;
        }

        return parent::sendSms($recipients, $message, $paymentMethod);
    }

    /**
     * The handsets this system may text, in the gateway's own 9-digit form.
     *
     * Read fresh on each send rather than cached on the instance: the service
     * is resolved once and reused for the life of the process, so a value
     * changed afterwards would otherwise never be seen.
     *
     * @return array<int, string>
     */
    protected function allowedNumbers(): array
    {
        $configured = config('services.dialog_sms.allowed_numbers', []);

        if (!is_array($configured)) {
            $configured = explode(',', (string) $configured);
        }

        $allowed = [];

        foreach ($configured as $number) {
            $formatted = $this->formatNumber(trim((string) $number));

            if ($formatted !== '') {
                $allowed[] = $formatted;
            }
        }

        return array_values(array_unique($allowed));
    }

    /**
     * Decide who this text may actually be delivered to.
     *
     * Anything aimed elsewhere is redirected to the permitted handsets rather
     * than dropped, because a dropped reminder is a test that silently proves
     * nothing -- the text still has to arrive and be read. The intended
     * recipient is written into the message so it stays obvious who each text
     * was really for.
     *
     * @param  array<int, string>  $numbers
     * @return array{0: array<int, string>, 1: string}
     */
    protected function applyAllowList(array $numbers, string $message): array
    {
        $requested = [];

        foreach ($numbers as $number) {
            $formatted = $this->formatNumber((string) $number);

            if ($formatted !== '') {
                $requested[] = $formatted;
            }
        }

        $requested = array_values(array_unique($requested));
        $allowed = $this->allowedNumbers();

        // Production: no list, no restriction.
        if ($allowed === []) {
            return [$requested, $message];
        }

        $permitted = array_values(array_intersect($requested, $allowed));
        $blocked = array_values(array_diff($requested, $allowed));

        if ($blocked === []) {
            return [$permitted, $message];
        }

        // The numbers only, never the body: an OTP or a customer's arrears
        // figure would otherwise sit in the log in clear.
        Log::warning('SMS redirected to the allow-list', [
            'intended'  => $blocked,
            'delivered' => $allowed,
        ]);

        // Union, so a text addressed to one permitted handset and one customer
        // is not delivered twice to the permitted one.
        $recipients = array_values(array_unique(array_merge($permitted, $allowed)));

        return [$recipients, '[TEST -> ' . implode(', ', $blocked) . '] ' . $message];
    }
}
