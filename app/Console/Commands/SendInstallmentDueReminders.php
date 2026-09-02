<?php

namespace App\Console\Commands;

use App\Models\LoanInstallment;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendInstallmentDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'installments:send-due-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send SMS reminders to customers whose loan installment is due in 3 days';

    public function handle(NotificationService $notificationService): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $installments = LoanInstallment::whereDate('due_date', $targetDate)
            ->whereIn('status', ['upcoming', 'partially_paid'])
            ->with('loanApplication.customer', 'loanApplication.loanApplicationCustomers.customer')
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            $loanApplication = $installment->loanApplication;

            if (!$loanApplication) {
                continue;
            }

            $isJoint = $loanApplication->isJointLoan();
            $dueMessage = "Reminder: Your loan installment is due on {$installment->due_date->format('Y-m-d')}. Please make your payment on time.";

            foreach ($loanApplication->notifiableCustomers() as $customer) {
                if (empty($customer->phone_primary)) {
                    continue;
                }

                $notificationService->sendSms(
                    'installment_due_reminder',
                    $customer->phone_primary,
                    $dueMessage,
                    [
                        'loan_application_id' => $installment->loan_application_id,
                        'customer_id' => $customer->id,
                    ]
                );

                if ($isJoint && !empty($customer->email)) {
                    $notificationService->sendEmail(
                        'installment_due_reminder',
                        $customer->email,
                        'Installment Due Reminder',
                        $dueMessage,
                        [
                            'loan_application_id' => $installment->loan_application_id,
                            'customer_id' => $customer->id,
                        ]
                    );
                }

                $sent++;
            }
        }

        $this->info("Installment due reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
