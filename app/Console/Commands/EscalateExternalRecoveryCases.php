<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\RecoveryCase;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
    protected $description = 'Close open internal recovery cases and open a new external recovery case once the configured external recovery threshold is crossed.';

    public function handle(NotificationService $notificationService): int
    {
        if (!Setting::get('recovery_escalation_enabled', true)) {
            $this->info('Recovery escalation is disabled in System Settings. Skipping.');
            return self::SUCCESS;
        }

        $thresholdDays = Setting::get('external_recovery_threshold_days', 45);

        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->whereHas('recoveryCases', function ($query) {
                $query->where('stage', 'internal')->whereIn('status', ['open', 'in_progress']);
            })
            ->with(['customer', 'installments', 'recoveryCases' => function ($query) {
                $query->where('stage', 'internal')->whereIn('status', ['open', 'in_progress']);
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

            $daysOverdue = $earliestOverdueInstallment->due_date->diffInDays(now());

            if ($daysOverdue < $thresholdDays) {
                continue;
            }

            $internalCase = $loanApplication->recoveryCases->first();

            if (!$internalCase) {
                continue;
            }

            $internalCase->update([
                'status'    => 'closed',
                'closed_at' => now(),
                'remarks'   => trim(($internalCase->remarks ?? '') . " Escalated to external recovery after {$daysOverdue} day(s) overdue."),
            ]);

            $overdueAmount = $loanApplication->installments
                ->where('status', 'overdue')
                ->sum('balance');

            $externalCase = RecoveryCase::create([
                'loan_application_id' => $loanApplication->id,
                'case_no'             => (string) Str::uuid(),
                'status'              => 'open',
                'stage'               => 'external',
                'parent_case_id'      => $internalCase->id,
                'overdue_amount'      => $overdueAmount,
                'opened_by'           => null,
                'opened_at'           => now(),
                'remarks'             => "Automatically escalated to external recovery after {$daysOverdue} day(s) overdue.",
            ]);
            $externalCase->case_no = 'RC-' . str_pad($externalCase->id, 6, '0', STR_PAD_LEFT);
            $externalCase->save();

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
