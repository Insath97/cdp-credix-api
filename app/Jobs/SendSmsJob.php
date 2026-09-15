<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::info('Processing SendSmsJob', [
            'notification_id' => $this->notificationId,
            'numbers'         => $this->numbers,
        ]);

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

        if ($sent) {
            Log::info('SendSmsJob completed successfully', [
                'notification_id' => $this->notificationId,
                'numbers'         => $this->numbers,
            ]);
        } else {
            Log::error('SendSmsJob failed: SMS gateway rejected the message', [
                'notification_id' => $this->notificationId,
                'numbers'         => $this->numbers,
            ]);
        }
    }
}
