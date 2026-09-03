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

        // Skip a loan that already has ANY live case, not just a live internal
        // one. Checking only stage=internal meant that once a case had been
        // escalated to external (which settles the internal one), this command
        // saw "no live internal case" and opened a fresh internal case the
        // very next night — which the external command then escalated again,
        // producing a new case pair every single night the loan stayed overdue.
        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->whereDoesntHave('recoveryCases', function ($query) {
                $query->whereIn('status', RecoveryCaseService::LIVE_STATUSES);
            })
            ->with(['customer', 'installments'])
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

            $recoveryCaseService->openInternalCase(
                $loanApplication,
                $recoveryCaseService->overdueAmountFor($loanApplication),
                $daysOverdue
            );

            $internalEscalationMessage = "CDP Credix: Your loan account (Loan Application ID: {$loanApplication->id}) has become overdue. Please contact us immediately to avoid further recovery actions.";

            foreach ($loanApplication->notifiableCustomers() as $notifyCustomer) {
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

        $summary = "Loan applications escalated to internal recovery: {$escalated}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'RecoveryCase', $summary, ['escalated' => $escalated]);

        return self::SUCCESS;
    }
}
