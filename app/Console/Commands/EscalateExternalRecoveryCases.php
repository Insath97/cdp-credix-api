<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Services\RecoveryCaseService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;

class EscalateExternalRecoveryCases extends Command
{
    use ActivityLogTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'recovery:escalate-external';

    /**
     * The console command description.
     */
    protected $description = 'Supersede live internal recovery cases and open an external recovery case once the configured external recovery threshold is crossed.';

    public function handle(NotificationService $notificationService, RecoveryCaseService $recoveryCaseService): int
    {
        if (!Setting::get('recovery_escalation_enabled', true)) {
            $this->info('Recovery escalation is disabled in System Settings. Skipping.');
            return self::SUCCESS;
        }

        $thresholdDays = (int) Setting::get('external_recovery_threshold_days', 45);

        // Every live case is loaded, both stages, because the "already has a
        // live external case" guard is now a per-party fact rather than a
        // per-loan one: on a Group Loan one member can be at the external stage
        // while another is still internal. It still checks only for a LIVE
        // external case, so a borrower who catches up (cases get resolved) and
        // later falls behind again correctly starts a new cycle.
        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->whereHas('recoveryCases', function ($query) {
                $query->where('stage', 'internal')->whereIn('status', RecoveryCaseService::LIVE_STATUSES);
            })
            ->with(['application', 'customer', 'installments', 'loanApplicationCustomers.customer', 'recoveryCases' => function ($query) {
                $query->whereIn('status', RecoveryCaseService::LIVE_STATUSES)->orderBy('id');
            }])
            ->get();

        $escalated = 0;

        foreach ($loanApplications as $loanApplication) {
            foreach ($loanApplication->arrearsGroups(fn ($installment) => $installment->status === 'overdue') as $arrears) {
                $daysOverdue = $arrears['installment']->daysOverdue();

                if ($daysOverdue < $thresholdDays) {
                    continue;
                }

                // Only this party's own cases. customer_id is null on Individual
                // and Joint loans, matching the single null-cased row, so this
                // behaves exactly as the loan-wide lookup it replaces.
                $partyCases = $loanApplication->recoveryCases
                    ->filter(fn ($case) => $case->customer_id === $arrears['customer_id']);

                $internalCase = $partyCases->firstWhere('stage', 'internal');
                $hasLiveExternalCase = $partyCases->contains(fn ($case) => $case->stage === 'external');

                if (!$internalCase || $hasLiveExternalCase) {
                    continue;
                }

                $recoveryCaseService->escalateToExternal(
                    $internalCase,
                    $loanApplication,
                    $recoveryCaseService->overdueAmountFor($loanApplication, $arrears['customer_id']),
                    $daysOverdue
                );

                $externalEscalationMessage = "Your overdue loan account ({$loanApplication->reference()}) has been referred to external recovery. Please settle your outstanding balance immediately.";

                foreach ($arrears['customers'] as $notifyCustomer) {
                    if (!empty($notifyCustomer->phone_primary)) {
                        $notificationService->sendSms(
                            'recovery_case_escalated_external_customer',
                            $notifyCustomer->phone_primary,
                            $externalEscalationMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }

                    if ($loanApplication->isJointLoan() && !empty($notifyCustomer->email)) {
                        $notificationService->sendEmail(
                            'recovery_case_escalated_external_customer',
                            $notifyCustomer->email,
                            'Recovery Case Escalated',
                            $externalEscalationMessage,
                            ['loan_application_id' => $loanApplication->id, 'customer_id' => $notifyCustomer->id]
                        );
                    }
                }

                $escalated++;
            }
        }

        $summary = "Loan applications escalated to external recovery: {$escalated}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'RecoveryCase', $summary, ['escalated' => $escalated]);

        return self::SUCCESS;
    }
}
