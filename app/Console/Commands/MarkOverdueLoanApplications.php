<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Services\LoanApplicationWorkflowService;
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

    public function handle(LoanApplicationWorkflowService $workflowService): int
    {
        $installmentsMarked = 0;
        $applicationsMarkedOverdue = 0;
        $applicationsReverted = 0;

        $activeApplications = LoanApplication::whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])
            ->with(['loanProduct', 'installments'])
            ->get();

        foreach ($activeApplications as $loanApplication) {
            $graceDays = $loanApplication->loanProduct?->grace_period_days
                ?: Setting::get('installment_due_period_days', 30);

            foreach ($loanApplication->installments as $installment) {
                if (
                    in_array($installment->status, ['upcoming', 'due', 'partially_paid'], true)
                    && $installment->balance > 0
                    && $installment->due_date->copy()->addDays($graceDays)->lt(now())
                ) {
                    $installment->update(['status' => 'overdue']);
                    $installmentsMarked++;
                }
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
        }

        $summary = "Installments marked overdue: {$installmentsMarked}. Applications marked overdue: {$applicationsMarkedOverdue}. Applications reverted to active: {$applicationsReverted}.";
        $this->info($summary);

        $this->logActivity('UPDATE', 'LoanApplication', $summary, [
            'installments_marked'        => $installmentsMarked,
            'applications_marked_overdue' => $applicationsMarkedOverdue,
            'applications_reverted'       => $applicationsReverted,
        ]);

        return self::SUCCESS;
    }
}
