<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\Notification;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;

class SendOverdueSmsReminders extends Command
{
    use ActivityLogTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'installments:send-overdue-sms';

    /**
     * The console command description.
     */
    protected $description = 'Send periodic SMS reminders to customers with overdue loan applications, on the frequency/duration configured in System Settings.';

    public function handle(NotificationService $notificationService): int
    {
        if (!Setting::get('sms_notifications_enabled', true)) {
            $this->info('SMS notifications are disabled in System Settings. Skipping.');
            return self::SUCCESS;
        }

        $frequencyDays = Setting::get('overdue_sms_frequency_days', 7);
        $durationDays = Setting::get('overdue_sms_duration_weeks', 3) * 7;

        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->with(['customer', 'installments'])
            ->get();

        $sent = 0;

        foreach ($loanApplications as $loanApplication) {
            $customer = $loanApplication->customer;

            if (!$customer || empty($customer->phone_primary)) {
                continue;
            }

            $earliestOverdueInstallment = $loanApplication->installments
                ->where('status', 'overdue')
                ->sortBy('due_date')
                ->first();

            if (!$earliestOverdueInstallment) {
                continue;
            }

            $daysOverdue = $earliestOverdueInstallment->due_date->diffInDays(now());

            if ($daysOverdue > $durationDays || $daysOverdue % $frequencyDays !== 0) {
                continue;
            }

            $alreadySentToday = Notification::where('loan_application_id', $loanApplication->id)
                ->where('type', 'overdue_sms_reminder')
                ->where('channel', 'sms')
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($alreadySentToday) {
                continue;
            }

            $notificationService->sendSms(
                'overdue_sms_reminder',
                $customer->phone_primary,
                "CDP Credix: Your loan installment is {$daysOverdue} day(s) overdue. Please make your payment as soon as possible to avoid recovery action.",
                ['loan_application_id' => $loanApplication->id, 'customer_id' => $customer->id]
            );

            $sent++;
        }

        $summary = "Overdue SMS reminders sent: {$sent}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'Notification', $summary, ['sent' => $sent]);

        return self::SUCCESS;
    }
}
