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
    protected $description = 'Send periodic SMS reminders to customers whose installment has passed its due date, on the frequency/duration configured in System Settings.';

    public function handle(NotificationService $notificationService): int
    {
        if (!Setting::get('sms_notifications_enabled', true)) {
            $this->info('SMS notifications are disabled in System Settings. Skipping.');
            return self::SUCCESS;
        }

        $frequencyDays = max(1, (int) Setting::get('overdue_sms_frequency_days', 7));
        $durationDays = (int) Setting::get('overdue_sms_duration_weeks', 3) * 7;

        // These are nudge reminders sent DURING the grace period — days 7, 14
        // and 21 after the due date, with stock settings — so they key off
        // "unpaid and past its due date", not off the formal 'overdue' status.
        // That status is only stamped once the product's grace period (30 days
        // by default) has fully elapsed, which is strictly later than this
        // whole reminder window: keying off it meant no reminder ever sent.
        $loanApplications = LoanApplication::whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])
            ->whereHas('installments', function ($query) {
                $query->whereNotIn('status', ['paid', 'waived', 'revised'])
                    ->where('balance', '>', 0)
                    ->whereDate('due_date', '<', now()->toDateString());
            })
            ->with(['customer', 'installments', 'loanApplicationCustomers.customer'])
            ->get();

        $sent = 0;

        foreach ($loanApplications as $loanApplication) {
            $isJoint = $loanApplication->isJointLoan();

            // One entry per party actually in arrears. For a Group Loan that is
            // one entry per member who has missed a payment — members who paid
            // on time produce no entry and are never reminded.
            foreach ($loanApplication->arrearsGroups(fn ($installment) => $installment->isPastDue()) as $arrears) {
                $daysOverdue = $arrears['installment']->daysOverdue();

                if ($daysOverdue < 1 || $daysOverdue > $durationDays || $daysOverdue % $frequencyDays !== 0) {
                    continue;
                }

                $overdueMessage = "CDP Credix: Your loan installment is {$daysOverdue} day(s) overdue. Please make your payment as soon as possible to avoid recovery action.";

                foreach ($arrears['customers'] as $customer) {
                    if (empty($customer->phone_primary)) {
                        continue;
                    }

                    $alreadySentToday = Notification::where('loan_application_id', $loanApplication->id)
                        ->where('customer_id', $customer->id)
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
                        $overdueMessage,
                        ['loan_application_id' => $loanApplication->id, 'customer_id' => $customer->id]
                    );

                    if ($isJoint && !empty($customer->email)) {
                        $notificationService->sendEmail(
                            'overdue_sms_reminder',
                            $customer->email,
                            'Installment Overdue Reminder',
                            $overdueMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $customer->id]
                        );
                    }

                    $sent++;
                }
            }
        }

        $summary = "Overdue SMS reminders sent: {$sent}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'Notification', $summary, ['sent' => $sent]);

        return self::SUCCESS;
    }
}
