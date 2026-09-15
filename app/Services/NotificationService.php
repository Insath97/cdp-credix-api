<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Mail\GenericNotificationMail;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Log and send an email notification. Failures are caught and recorded
     * on the Notification row rather than bubbling up, so a mail failure
     * never breaks the business action that triggered it.
     */
    public function sendEmail(string $type, string $recipientEmail, string $subject, string $message, array $context = [], ?string $logMessage = null): Notification
    {
        $notification = Notification::create(array_merge($context, [
            'type'      => $type,
            'channel'   => 'email',
            'recipient' => $recipientEmail,
            'subject'   => $subject,
            'message'   => $logMessage ?? $message,
            'status'    => 'pending',
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
        $notification = Notification::create(array_merge($context, [
            'type'      => $type,
            'channel'   => 'sms',
            'recipient' => $recipientPhone,
            'message'   => $logMessage ?? $message,
            'status'    => 'pending',
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
}
