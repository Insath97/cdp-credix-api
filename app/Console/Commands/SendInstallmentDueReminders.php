<?php

namespace App\Console\Commands;

use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

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
            ->with('customer', 'loanApplication.customer', 'loanApplication.loanApplicationCustomers.customer')
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            $loanApplication = $installment->loanApplication;

            if (!$loanApplication) {
                continue;
            }

            $isJoint = $loanApplication->isJointLoan();
            $dueMessage = "Reminder: Your loan installment is due on {$installment->due_date->format('Y-m-d')}. Please make your payment on time.";

            foreach ($this->recipientsFor($installment, $loanApplication) as $customer) {
                if (empty($customer->phone_primary)) {
                    continue;
                }

                // The same guard SendOverdueSmsReminders carries. Without it a
                // second run on the same day -- a manual invocation, a retry
                // after a crash part-way through the batch, or two schedulers
                // overlapping -- texted every borrower their reminder again.
                $alreadySentToday = Notification::where('loan_application_id', $installment->loan_application_id)
                    ->where('customer_id', $customer->id)
                    ->where('type', 'installment_due_reminder')
                    ->where('channel', 'sms')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if ($alreadySentToday) {
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

    /**
     * Who should hear about this particular installment.
     *
     * A Group Loan gives every member their own row per period, so a reminder
     * goes only to the member who actually owes it — otherwise a 5-member
     * group would send 5 reminders to all 5 members for the same due date.
     *
     * Individual and Joint Loans keep notifying every attached customer. Note
     * the branch keys off isGroupLoan() rather than the installment's
     * customer_id: those loans now stamp their primary customer on the row
     * too, so reading the column would silently stop notifying Joint Loan
     * co-borrowers.
     */
    private function recipientsFor(LoanInstallment $installment, LoanApplication $loanApplication): Collection
    {
        if ($loanApplication->isGroupLoan() && $installment->customer_id) {
            return collect([$installment->customer])->filter()->values();
        }

        return $loanApplication->notifiableCustomers();
    }
}
