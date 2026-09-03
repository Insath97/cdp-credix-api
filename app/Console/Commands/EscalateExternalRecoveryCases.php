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

        // The whereDoesntHave guard is the other half of the duplicate-case
        // fix: even if a stray live internal case exists, a loan that already
        // has a live external case must never get a second one. It checks only
        // for a LIVE external case, so a borrower who catches up (cases get
        // resolved) and later falls behind again correctly starts a new cycle.
        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->whereHas('recoveryCases', function ($query) {
                $query->where('stage', 'internal')->whereIn('status', RecoveryCaseService::LIVE_STATUSES);
            })
            ->whereDoesntHave('recoveryCases', function ($query) {
                $query->where('stage', 'external')->whereIn('status', RecoveryCaseService::LIVE_STATUSES);
            })
            ->with(['customer', 'installments', 'recoveryCases' => function ($query) {
                $query->where('stage', 'internal')
                    ->whereIn('status', RecoveryCaseService::LIVE_STATUSES)
                    ->orderBy('id');
            }])
            ->get();

        $escalated = 0;

        foreach ($loanApplications as $loanApplication) {
            $earliestOverdueInstallment = $loanApplication->installments
                ->where('status', 'overdue')
                ->sortBy('due_date')
                ->first();

            if (!$earliestOverdueInstallment) {
                continue;
            }

            $daysOverdue = $earliestOverdueInstallment->daysOverdue();

            if ($daysOverdue < $thresholdDays) {
                continue;
            }

            $internalCase = $loanApplication->recoveryCases->first();

            if (!$internalCase) {
                continue;
            }

            $recoveryCaseService->escalateToExternal(
                $internalCase,
                $loanApplication,
                $recoveryCaseService->overdueAmountFor($loanApplication),
                $daysOverdue
            );

            $externalEscalationMessage = "CDP Credix: Your overdue loan account (Loan Application ID: {$loanApplication->id}) has been referred to external recovery. Please settle your outstanding balance immediately.";

            foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
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

        $summary = "Loan applications escalated to external recovery: {$escalated}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'RecoveryCase', $summary, ['escalated' => $escalated]);

        return self::SUCCESS;
    }
}
