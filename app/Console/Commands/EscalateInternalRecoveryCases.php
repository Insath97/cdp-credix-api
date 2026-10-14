<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Services\RecoveryCaseService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;

class EscalateInternalRecoveryCases extends Command
{
    use ActivityLogTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'recovery:escalate-internal';

    /**
     * The console command description.
     */
    protected $description = 'Automatically open an internal recovery case for overdue loan applications that have crossed the configured internal recovery threshold.';

    public function handle(NotificationService $notificationService, RecoveryCaseService $recoveryCaseService): int
    {
        if (!Setting::get('recovery_escalation_enabled', true)) {
            $this->info('Recovery escalation is disabled in System Settings. Skipping.');
            return self::SUCCESS;
        }

        $thresholdDays = (int) Setting::get('internal_recovery_threshold_days', 30);

        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->with(['application', 'customer', 'installments', 'loanApplicationCustomers.customer'])
            ->get();

        $escalated = 0;

        foreach ($loanApplications as $loanApplication) {
            // One entry per party in arrears — per member on a Group Loan, so
            // the case identifies who actually missed a payment and the members
            // who paid on time are left alone.
            foreach ($loanApplication->arrearsGroups(fn ($installment) => $installment->status === 'overdue') as $arrears) {
                // Skip a party that already has ANY live case, not just a live
                // internal one. Checking only stage=internal meant that once a
                // case had been escalated to external (which settles the
                // internal one), this command saw "no live internal case" and
                // opened a fresh internal case the very next night — which the
                // external command then escalated again, producing a new case
                // pair every single night the loan stayed overdue.
                //
                // The check is per party rather than per loan: a Group Loan
                // member's open case must not stop a different member who falls
                // behind later from getting their own case. For Individual and
                // Joint loans customer_id is null, so this is exactly the
                // loan-wide guard it replaces.
                if ($recoveryCaseService->hasLiveCaseFor($loanApplication, $arrears['customer_id'])) {
                    continue;
                }

                $daysOverdue = $arrears['installment']->daysOverdue();

                if ($daysOverdue < $thresholdDays) {
                    continue;
                }

                $recoveryCaseService->openInternalCase(
                    $loanApplication,
                    $recoveryCaseService->overdueAmountFor($loanApplication, $arrears['customer_id']),
                    $daysOverdue,
                    $arrears['customer_id']
                );

                $internalEscalationMessage = "Your loan account has become overdue. Please contact us immediately to avoid further recovery actions.";

                foreach ($arrears['customers'] as $notifyCustomer) {
                    if (!empty($notifyCustomer->phone_primary)) {
                        $notificationService->sendSms(
                            'recovery_case_opened_customer',
                            $notifyCustomer->phone_primary,
                            $internalEscalationMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }

                    if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                        $notificationService->sendEmail(
                            'recovery_case_opened_customer',
                            $notifyCustomer->email,
                            'Recovery Case Opened',
                            $internalEscalationMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }
                }

                $escalated++;
            }
        }

        $summary = "Loan applications escalated to internal recovery: {$escalated}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'RecoveryCase', $summary, ['escalated' => $escalated]);

        return self::SUCCESS;
    }
}
