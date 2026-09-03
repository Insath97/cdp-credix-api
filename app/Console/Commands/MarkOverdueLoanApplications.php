<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Services\LoanApplicationWorkflowService;
use App\Services\RecoveryCaseService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;

class MarkOverdueLoanApplications extends Command
{
    use ActivityLogTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'loans:mark-overdue';

    /**
     * The console command description.
     */
    protected $description = 'Mark installments overdue once their grace period has elapsed, and sync the parent loan application status (Active <-> Overdue) accordingly.';

    public function handle(LoanApplicationWorkflowService $workflowService, RecoveryCaseService $recoveryCaseService): int
    {
        $installmentsMarked = 0;
        $applicationsMarkedOverdue = 0;
        $applicationsReverted = 0;
        $penaltiesCharged = 0;
        $casesResolved = 0;

        $activeApplications = LoanApplication::whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])
            ->with(['loanProduct', 'installments', 'recoveryCases' => function ($query) {
                $query->whereIn('status', RecoveryCaseService::LIVE_STATUSES);
            }])
            ->get();

        foreach ($activeApplications as $loanApplication) {
            $graceDays = $loanApplication->loanProduct?->grace_period_days
                ?: Setting::get('installment_due_period_days', 30);
            $penaltyValue = $loanApplication->loanProduct?->penalty_value;

            foreach ($loanApplication->installments as $installment) {
                if (
                    in_array($installment->status, ['upcoming', 'due', 'partially_paid'], true)
                    && $installment->balance > 0
                    && $installment->due_date->copy()->addDays($graceDays)->lt(now())
                ) {
                    // A flat, one-time late fee: only ever charged the first time this
                    // installment goes overdue (guarded by penalty_amount still being 0),
                    // never re-charged on later scheduler runs for the same installment.
                    if ($penaltyValue > 0 && $installment->penalty_amount == 0) {
                        $installment->penalty_amount = $penaltyValue;
                        $installment->recalculateBalance();
                        if ($loanApplication->outstanding_balance !== null) {
                            $loanApplication->outstanding_balance += $penaltyValue;
                        }
                        $penaltiesCharged++;
                    }

                    $installment->status = 'overdue';
                    $installment->save();
                    $installmentsMarked++;
                }
            }

            if ($loanApplication->isDirty('outstanding_balance')) {
                $loanApplication->save();
            }

            $hasOverdueInstallment = $loanApplication->installments->contains(
                fn ($installment) => $installment->status === 'overdue'
            );

            try {
                if ($hasOverdueInstallment && $loanApplication->status === LoanApplicationStatus::Active) {
                    $workflowService->transition(
                        $loanApplication,
                        LoanApplicationStatus::Overdue,
                        null,
                        'Automatically marked overdue: installment past its grace period'
                    );
                    $applicationsMarkedOverdue++;
                } elseif (!$hasOverdueInstallment && $loanApplication->status === LoanApplicationStatus::Overdue) {
                    $workflowService->transition(
                        $loanApplication,
                        LoanApplicationStatus::Active,
                        null,
                        'Automatically reverted to active: all installments caught up'
                    );
                    $applicationsReverted++;
                }
            } catch (\Throwable $th) {
                $this->error("Failed to sync status for loan application ID {$loanApplication->id}: {$th->getMessage()}");
            }

            // A loan that is no longer in arrears must not leave a live
            // recovery case behind: the escalation commands skip any loan that
            // already has one, so a stale case would permanently block a
            // legitimate future case. Checked on !$hasOverdueInstallment
            // rather than only inside the revert branch above, so loans that
            // are already Active but carry a stale case get cleaned up too.
            if (!$hasOverdueInstallment && $loanApplication->recoveryCases->isNotEmpty()) {
                $casesResolved += $recoveryCaseService->resolveOpenCases(
                    $loanApplication,
                    'Automatically resolved: the loan application is no longer overdue.'
                );
            }
        }

        $summary = "Installments marked overdue: {$installmentsMarked}. Penalties charged: {$penaltiesCharged}. Applications marked overdue: {$applicationsMarkedOverdue}. Applications reverted to active: {$applicationsReverted}. Recovery cases resolved: {$casesResolved}.";
        $this->info($summary);

        $this->logActivity('UPDATE', 'LoanApplication', $summary, [
            'installments_marked'        => $installmentsMarked,
            'penalties_charged'          => $penaltiesCharged,
            'applications_marked_overdue' => $applicationsMarkedOverdue,
            'applications_reverted'       => $applicationsReverted,
            'recovery_cases_resolved'     => $casesResolved,
        ]);

        return self::SUCCESS;
    }
}
