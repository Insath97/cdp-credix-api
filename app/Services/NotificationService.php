<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Mail\GenericNotificationMail;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * How long an identical SMS to the same handset counts as a repeat of
     * the one already sent.
     *
     * Long enough to cover a whole fan-out (a group loan texting every
     * member, a staff role texting every officer) and a double-submitted
     * form; short enough that two genuinely separate events never collide,
     * since they would also have to produce byte-identical text.
     * Deliberately a constant rather than a System Setting -- it is a
     * property of what counts as the same message, not a business dial.
     */
    private const SMS_DEDUPE_WINDOW_MINUTES = 5;

    /**
     * The house style every customer-facing notification is written in.
     */
    private const GREETING  = 'Dear %s,';
    private const REFERENCE = 'Loan Ref: %s';
    private const SIGNATURE = "Best Wishes,\nCDP Capital (PVT) LTD.";

    /**
     * Log and send an email notification. Failures are caught and recorded
     * on the Notification row rather than bubbling up, so a mail failure
     * never breaks the business action that triggered it.
     */
    public function sendEmail(string $type, string $recipientEmail, string $subject, string $message, array $context = [], ?string $logMessage = null): Notification
    {
        $message = $this->personalise($message, $context);

        $notification = Notification::create(array_merge($context, [
            'type'         => $type,
            'channel'      => 'email',
            'recipient'    => $recipientEmail,
            'subject'      => $subject,
            'message'      => $logMessage ?? $message,
            'message_hash' => hash('sha256', $message),
            'status'       => 'pending',
        ]));

        try {
            Mail::to($recipientEmail)->send(new GenericNotificationMail($subject, $message));

            $notification->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("Notification email sent successfully", [
                'notification_id' => $notification->id,
                'type'            => $type,
                'recipient'       => $recipientEmail,
                'subject'         => $subject,
            ]);
        } catch (\Throwable $th) {
            Log::error("Failed to send notification email: " . $th->getMessage(), [
                'notification_id' => $notification->id,
                'type'            => $type,
                'recipient'       => $recipientEmail,
                'subject'         => $subject,
            ]);

            $notification->update([
                'status' => 'failed',
                'error'  => $th->getMessage(),
            ]);
        }

        return $notification;
    }

    /**
     * Log and queue an SMS notification. Failures are caught and recorded
     * on the Notification row rather than bubbling up, so an SMS failure
     * never breaks the business action that triggered it.
     *
     * The row starts as 'pending' and is flipped to 'sent'/'failed' by
     * SendSmsJob once the queued job actually processes the send.
     */
    public function sendSms(string $type, string $recipientPhone, string $message, array $context = [], ?string $logMessage = null): Notification
    {
        $message = $this->personalise($message, $context);

        if ($alreadySent = $this->recentIdenticalSms($type, $recipientPhone, $message)) {
            Log::info('Skipped a duplicate notification SMS', [
                'notification_id' => $alreadySent->id,
                'type'            => $type,
                'recipient'       => $recipientPhone,
            ]);

            return $alreadySent;
        }

        $notification = Notification::create(array_merge($context, [
            'type'         => $type,
            'channel'      => 'sms',
            'recipient'    => $recipientPhone,
            'message'      => $logMessage ?? $message,
            'message_hash' => hash('sha256', $message),
            'status'       => 'pending',
        ]));

        try {
            SendSmsJob::dispatchSync($recipientPhone, $message, $notification->id);

            Log::info("Notification SMS sent successfully", [
                'notification_id' => $notification->id,
                'type'            => $type,
                'recipient'       => $recipientPhone,
            ]);
        } catch (\Throwable $th) {
            Log::error("Failed to send notification SMS: " . $th->getMessage(), [
                'notification_id' => $notification->id,
                'type'            => $type,
                'recipient'       => $recipientPhone,
            ]);

            $notification->update([
                'status' => 'failed',
                'error'  => $th->getMessage(),
            ]);
        }

        return $notification;
    }

    /**
     * Add the loan number to a message, and for a customer wrap it in the
     * house format:
     *
     *     Dear <customer>,
     *
     *     <message>
     *
     *     Best Wishes,
     *     CDP Capital (PVT) LTD.
     *
     * Only notifications addressed to a borrower are wrapped, and the context's
     * customer_id is what says so. That is not a heuristic: every customer
     * notification in the app passes customer_id and every staff or recovery
     * agent one passes user_id or nothing, so the key that names the reader is
     * also the key that supplies the greeting. An internal "please assign an
     * agent" text is left exactly as written.
     *
     * Applied before the duplicate check, so the guard compares the message
     * that actually reaches the handset rather than the raw template.
     */
    private function personalise(string $message, array $context): string
    {
        $reference = $this->loanReferenceFor($context);

        // Staff and recovery agents: no greeting and no sign-off -- those read
        // as a form letter on an operational note -- but the number still
        // belongs, because "New loan application pending for review" names no
        // file at all and a reviewer had nothing to search for. Appended at the
        // end so the instruction stays the first thing read.
        if (empty($context['customer_id'])) {
            return $reference
                ? $message . "\n" . sprintf(self::REFERENCE, $reference)
                : $message;
        }

        $name = Customer::whereKey($context['customer_id'])->value('full_name');

        $head = sprintf(self::GREETING, $name ?: 'Customer');

        // The file the message is about, quoted so a customer ringing back can
        // say which loan they mean and an officer can find it without asking
        // for a name and a date. Added here rather than in each message,
        // because every customer notification carries loan_application_id and
        // the ones that used to paste the reference into their own wording
        // each did it differently.
        //
        // Absent only where there is genuinely no loan: the registration
        // credentials SMS names no application, and adding an empty line to it
        // would look like a fault.
        if ($reference) {
            $head .= "\n\n" . sprintf(self::REFERENCE, $reference);
        }

        return $head
            . "\n\n" . $message
            . "\n\n" . self::SIGNATURE;
    }

    /**
     * The human-readable number of the loan this notification is about.
     *
     * LoanApplication::reference() answers the application number the customer
     * has held since the file was taken -- not the approval reference, which
     * only exists from approval onwards and would mean the number in their
     * messages changed halfway through.
     */
    private function loanReferenceFor(array $context): ?string
    {
        if (empty($context['loan_application_id'])) {
            return null;
        }

        return LoanApplication::with('application:id,application_no')
            ->find($context['loan_application_id'])
            ?->reference();
    }

    /**
     * The identical SMS already on its way to this handset, if there is one.
     *
     * Two customers on one loan can carry the same mobile -- a group's members
     * sharing the leader's phone, a husband and wife on a joint loan -- and
     * customers.phone_primary has no uniqueness in the schema or in any
     * validation rule to stop it. Every fan-out loop then texts that one
     * handset once per customer: a five-member group loan sent the same
     * "successfully disbursed" message five times from a single disburse.
     *
     * Matching is on the normalised number, because the same phone is stored
     * as 0752932640 on one member and +94752932640 on another, and only the
     * gateway ever collapsed the two.
     *
     * Sameness is judged on message_hash rather than on the stored `message`,
     * because they are not always the same string. The credentials SMS stores
     * a summary in `message` to keep a plaintext password out of a log this
     * app serves to admins, so comparing the column would have read every
     * customer's credentials as one identical message and swallowed the second
     * customer's password. The hash is of what was really sent, so two
     * customers sharing a handset still each get their own -- and the same
     * customer's form submitted twice does not.
     */
    private function recentIdenticalSms(string $type, string $recipientPhone, string $message): ?Notification
    {
        $normalised = SmsService::normalise($recipientPhone);

        if ($normalised === '') {
            return null;
        }

        return Notification::where('channel', 'sms')
            ->where('type', $type)
            ->where('message_hash', hash('sha256', $message))
            ->whereIn('status', ['pending', 'sent'])
            ->where('created_at', '>=', now()->subMinutes(self::SMS_DEDUPE_WINDOW_MINUTES))
            ->latest('id')
            ->get()
            ->first(fn (Notification $sent) => SmsService::normalise((string) $sent->recipient) === $normalised);
    }
}
