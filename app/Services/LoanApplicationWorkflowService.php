<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoanApplicationWorkflowService
{
    public function __construct(
        protected InstallmentScheduleService $installmentScheduleService,
        protected ReferenceNumberService $referenceNumberService,
    ) {
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

        $this->assertSegregationOfDuties($loanApplication, $to, $actorId);

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
            // The approval reference, minted the first time this application is
            // approved. Guarded on being null rather than on the transition
            // alone: a loan that is reverted and approved again keeps the
            // reference the customer was already given. Done inside this
            // transaction so its counter lookup is covered by the same lock
            // that ReferenceNumberService takes.
            if ($to === LoanApplicationStatus::Approved && empty($loanApplication->approval_reference_no)) {
                $extra['approval_reference_no'] = $this->referenceNumberService->forApproval($loanApplication);
            }

            $loanApplication->update(array_merge($extra, [
                'status' => $to,
                // A finished loan application must never stay flagged active:
                // is_active is what the listings filter on and what the status
                // badge reads, so a cancelled loan left active still shows as
                // "Active". Applied here rather than in each controller so
                // cancel/reject/close all get it, including the Group Loan
                // cascade, which transitions through this same method.
                'is_active' => $to->isTerminal() ? false : $loanApplication->is_active,
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
                'verifiedByUser',
                'approvedByUser',
                'assignedReviewer',
                'installments',
            ]);
        });
    }

    /**
     * A loan passes through three separate hands before money moves — review,
     * verify, approve — and no one person may cover two of them.
     *
     * The rule is enforced here rather than in the controllers so it applies to
     * Individual, Joint and Group loans alike (the group cascade transitions
     * through this same method). Only the two later stages need checking: the
     * reviewer is by definition the first checker.
     *
     * Super Admin is exempt. Every other role is bound by its permissions plus
     * this rule; Super Admin is the role that exists to be able to act on
     * anything, and the rest of the app already treats it that way — it is the
     * only role that may delete a branch or manage admin users. The exemption
     * means a Super Admin can carry a file from review to approval alone.
     *
     * Turn off for everyone with the `loan_approval_segregation_enabled` System
     * Setting where one officer legitimately handles the whole file — a very
     * small branch — accepting that this removes the maker-checker control.
     */
    protected function assertSegregationOfDuties(
        LoanApplication $loanApplication,
        LoanApplicationStatus $to,
        ?int $actorId
    ): void {
        if ($actorId === null) {
            return;
        }

        if (!Setting::get('loan_approval_segregation_enabled', true)) {
            return;
        }

        if (User::find($actorId)?->isSuperAdmin()) {
            return;
        }

        $conflicts = match ($to) {
            LoanApplicationStatus::Verified => ['reviewed_by' => 'reviewed'],
            LoanApplicationStatus::Approved => ['reviewed_by' => 'reviewed', 'verified_by' => 'verified'],
            default => [],
        };

        foreach ($conflicts as $column => $stage) {
            if ((int) $loanApplication->{$column} !== $actorId) {
                continue;
            }

            $action = $to === LoanApplicationStatus::Verified ? 'verify' : 'approve';

            throw new InvalidLoanApplicationTransitionException(
                "You already {$stage} this loan application, so you cannot also {$action} it. "
                . 'Review, verification and approval must each be done by a different person.'
            );
        }
    }
}
