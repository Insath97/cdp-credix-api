<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

class LoanApplicationWorkflowService
{
    public function __construct(protected InstallmentScheduleService $installmentScheduleService)
    {
    }

    /**
     * Transition a loan application to a new status, guarded by the enum's
     * allowed-transition map, and record the change in status history.
     *
     * @param array $extra Additional fields to persist alongside the status change
     *                      (e.g. reviewed_by, approved_amount, rejection_reason).
     */
    public function transition(
        LoanApplication $loanApplication,
        LoanApplicationStatus $to,
        ?int $actorId = null,
        ?string $remarks = null,
        array $extra = []
    ): LoanApplication {
        $from = $loanApplication->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidLoanApplicationTransitionException(
                "Cannot transition loan application from '{$from->value}' to '{$to->value}'."
            );
        }

        // A guarantor is mandatory for Individual Loan before it can be
        // verified, but stays optional for Group Loan members.
        if ($to === LoanApplicationStatus::Verified
            && $loanApplication->group_loan_id === null
            && $loanApplication->loanApplicationGuarantors()->count() === 0) {
            throw new InvalidLoanApplicationTransitionException(
                'At least one guarantor is required before this loan application can be verified.'
            );
        }

        return DB::transaction(function () use ($loanApplication, $from, $to, $actorId, $remarks, $extra) {
            $loanApplication->update(array_merge($extra, [
                'status' => $to,
            ]));

            $loanApplication->application()->update([
                'status' => $to->toApplicationStatus(),
            ]);

            $loanApplication->statusHistory()->create([
                'from_status' => $from->value,
                'to_status'   => $to->value,
                'changed_by'  => $actorId,
                'remarks'     => $remarks,
            ]);

            if ($to === LoanApplicationStatus::Disbursed) {
                $this->installmentScheduleService->generate($loanApplication);
            }

            return $loanApplication->fresh([
                'application',
                'customer',
                'loanProduct',
                'branch',
                'appliedByUser',
                'reviewedByUser',
                'approvedByUser',
                'assignedReviewer',
                'installments',
            ]);
        });
    }
}
