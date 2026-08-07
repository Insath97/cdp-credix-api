<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;

    public function __construct(
        public string|array $numbers,
        public string $message,
        public ?int $notificationId = null,
    ) {
    }

    public function handle(SmsService $smsService): void
    {
        $sent = $smsService->sendSms($this->numbers, $this->message);

        if ($this->notificationId) {
            $notification = Notification::find($this->notificationId);

            if ($notification) {
                $notification->update($sent
                    ? ['status' => 'sent', 'sent_at' => now()]
                    : ['status' => 'failed', 'error' => 'SMS gateway rejected the message. Check logs for details.']
                );
            }
        }
    }
}
