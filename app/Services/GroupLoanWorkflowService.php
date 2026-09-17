<?php

namespace App\Services;

use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Models\GroupLoan;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

/**
 * Drives a group loan through its lifecycle.
 *
 * A group loan is **one** loan_applications row with its members attached via
 * loan_application_customers — the same shape Joint Loan uses. So every action
 * here cascades to exactly one application, and the installment schedule,
 * outstanding balance, overdue status, penalties and recovery cases are all
 * naturally group-level.
 *
 * Group loans carry no interest rate. Repayment is derived purely from the
 * group's service charge percentage, snapshotted onto the header at submission
 * from the `group_loan_service_charge_percentage` System Setting.
 */
class GroupLoanWorkflowService
{
    /**
     * The smallest a group can be. Hardcoded rather than a System Setting,
     * consistent with how the rest of the group loan rules are fixed.
     */
    public const MIN_MEMBERS = 2;

    /**
     * Relations worth loading on every group loan returned from here.
     */
    private const RESPONSE_RELATIONS = [
        'loanProduct',
        'branch',
        'items',
        'loanApplication.loanApplicationCustomers.customer.customerDetail',
        'loanApplication.installments',
        'appliedByUser',
        'reviewedByUser',
        'verifiedByUser',
        'approvedByUser',
    ];

    public function __construct(protected LoanApplicationWorkflowService $loanApplicationWorkflowService)
    {
    }

    /**
     * Transition the group loan header to a new lifecycle status, guarded by
     * GroupLoanStatus' own transition map. Unlike
     * LoanApplicationWorkflowService::transition(), this does not open its own
     * DB transaction — callers already wrap the full cascade in one — and there
     * is no group-level status history write; the cascaded transition on the
     * group's application already records the timeline via
     * loan_application_status_history.
     */
    public function transition(
        GroupLoan $groupLoan,
        GroupLoanStatus $to,
        ?int $actorId = null,
        ?string $remarks = null,
        array $extra = []
    ): GroupLoan {
        $from = $groupLoan->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidLoanApplicationTransitionException(
                "Cannot transition group loan from '{$from->value}' to '{$to->value}'."
            );
        }

        $groupLoan->update(array_merge($extra, [
            'status' => $to,
            // A finished group loan must never stay flagged active — see the
            // same rule in LoanApplicationWorkflowService::transition().
            'is_active' => $to->isTerminal() ? false : $groupLoan->is_active,
        ]));

        return $groupLoan->fresh(self::RESPONSE_RELATIONS);
    }

    /**
     * The group's single loan application, locked for update.
     *
     * Every money-moving action goes through this, so a group loan whose
     * application is somehow missing fails loudly rather than silently doing
     * nothing.
     */
    protected function applicationFor(GroupLoan $groupLoan): LoanApplication
    {
        $application = $groupLoan->loanApplication()->lockForUpdate()->first();

        if (!$application) {
            throw new InvalidLoanApplicationTransitionException(
                "Group loan '{$groupLoan->group_loan_no}' has no loan application attached."
            );
        }

        return $application;
    }

    /**
     * Whether the group's member list may still be changed.
     *
     * Members can be added, removed and edited while the group loan is
     * Available. Once it is approved (Locked) the list is frozen: by then the
     * approved amount has been split across exactly these members and the
     * schedule is pending. This is the single backend gate behind the
     * `can_modify_members` flag the frontend reads.
     */
    public function assertMembersModifiable(GroupLoan $groupLoan): void
    {
        if ($groupLoan->status !== GroupLoanStatus::Available) {
            throw new InvalidLoanApplicationTransitionException(
                "This group loan is {$groupLoan->status->value} and its members are locked. "
                . 'Members can only be changed while the group loan is still Available (before approval).'
            );
        }
    }

    /**
     * Bring the group's single application back in step with its header after
     * the item list, term or member list changed: mirror the requested amount
     * and term onto the application (and the generic applications row), and
     * refresh the member count from the pivot.
     *
     * Only meaningful before approval — after that the figures are frozen, and
     * the callers all guard on GroupLoanStatus::Available first.
     */
    public function syncAmounts(GroupLoan $groupLoan): GroupLoan
    {
        $application = $groupLoan->loanApplication()->lockForUpdate()->first();

        $memberCount = $application
            ? $application->loanApplicationCustomers()->count()
            : 0;

        $groupLoan->update([
            'number_of_members' => $memberCount ?: $groupLoan->number_of_members,
        ]);

        if ($application) {
            $application->update([
                'requested_amount' => $groupLoan->requested_amount,
                'term_months'      => $groupLoan->term_months,
            ]);

            // The generic applications row mirrors the same total.
            $application->application?->update([
                'requested_amount'        => $groupLoan->requested_amount,
                'repayment_period_months' => $groupLoan->term_months,
            ]);
        }

        return $groupLoan->refresh();
    }

    /**
     * Verify the group loan after review. The header status is deliberately
     * left at Available: a submitted and a verified group loan are both still
     * "open" as far as the lifecycle tabs are concerned, so only the review
     * audit fields move here. Reaching Verified is still enforced — the
     * cascade below runs through LoanApplicationStatus' own guard.
     */
    public function verify(GroupLoan $groupLoan, ?int $actorId, ?string $remarks, array $extra = []): GroupLoan
    {
        // Verifying a file that was sent back clears the reason it was sent
        // back for, and bumps the count of how many looks it has taken. Done
        // here rather than in the controller so the group and individual paths
        // cannot drift apart. The failure itself stays readable in
        // loan_application_status_history.
        if ($this->applicationFor($groupLoan)->status === LoanApplicationStatus::Reverify) {
            $extra['verify_failure_reason'] = null;
            $extra['verify_failed_at']      = null;
            $extra['reverify_count']        = (int) $groupLoan->reverify_count + 1;
        }

        return $this->advanceStage($groupLoan, LoanApplicationStatus::Verified, 'verify', $actorId, $remarks, $extra);
    }

    /**
     * Review the group loan — the first of the three hands before approval.
     * Like verify(), it leaves the header at Available; only the audit fields
     * and the cascaded member stage move.
     */
    public function review(GroupLoan $groupLoan, ?int $actorId, ?string $remarks, array $extra = []): GroupLoan
    {
        return $this->advanceStage($groupLoan, LoanApplicationStatus::Reviewed, 'review', $actorId, $remarks, $extra);
    }

    /**
     * Fail the verification of a group loan.
     *
     * Verification is where the flow forks: pass and the file goes on to
     * approval, fail and it lands in Reverify for a second look. The header
     * stays Available either way, exactly as review() and verify() leave it —
     * a group loan waiting to be checked again is still an open file.
     */
    public function verifyFail(GroupLoan $groupLoan, ?int $actorId, ?string $reason, array $extra = []): GroupLoan
    {
        return $this->advanceStage($groupLoan, LoanApplicationStatus::Reverify, 'fail the verification of', $actorId, $reason, $extra);
    }

    /**
     * Fail the review of a group loan and send it back to the group.
     *
     * The mirror of the individual loan's review-fail, and not a rejection:
     * rejection ends the file, this says the paperwork was not good enough to
     * check. The group is told by SMS, fixes its documents and resubmits.
     *
     * The header stays Available throughout, exactly as review() and verify()
     * leave it — a group loan waiting on better documents is still an open
     * file as far as the lifecycle tabs are concerned, and only the
     * application underneath it actually moves. advanceStage() is reused
     * because its guard ("the header must be Available") and its cascade are
     * both the right ones here; only the direction of travel differs, and
     * LoanApplicationStatus' own map is what decides that.
     */
    public function reviewFail(GroupLoan $groupLoan, ?int $actorId, ?string $reason, array $extra = []): GroupLoan
    {
        return $this->advanceStage($groupLoan, LoanApplicationStatus::ReviewFailed, 'fail the review of', $actorId, $reason, $extra);
    }

    /**
     * Put a failed group loan back in the review queue once its documents have
     * been reuploaded.
     *
     * Goes back to Submitted so the file re-enters review from the top rather
     * than skipping the stage it failed, and bumps the resubmission count on
     * both the header and the application — the count is what tells an officer
     * they are looking at a file on its fourth attempt.
     */
    public function resubmit(GroupLoan $groupLoan, ?int $actorId, ?string $remarks, array $extra = []): GroupLoan
    {
        return $this->advanceStage($groupLoan, LoanApplicationStatus::Submitted, 'resubmit', $actorId, $remarks, $extra);
    }

    /**
     * Move the group's application one stage along while the header stays
     * Available. The real stage guard — and the segregation-of-duties check —
     * live in LoanApplicationWorkflowService::transition(), so review and
     * verify inherit both from the same place an individual loan does.
     */
    protected function advanceStage(
        GroupLoan $groupLoan,
        LoanApplicationStatus $to,
        string $verb,
        ?int $actorId,
        ?string $remarks,
        array $extra
    ): GroupLoan {
        return DB::transaction(function () use ($groupLoan, $to, $verb, $actorId, $remarks, $extra) {
            if ($groupLoan->status !== GroupLoanStatus::Available) {
                throw new InvalidLoanApplicationTransitionException(
                    "Cannot {$verb} a group loan that is '{$groupLoan->status->value}'."
                );
            }

            if (!empty($extra)) {
                $groupLoan->update($extra);
            }

            $groupLoan->refresh();

            $this->loanApplicationWorkflowService->transition(
                $this->applicationFor($groupLoan),
                $to,
                $actorId,
                $remarks,
                $extra
            );

            return $groupLoan->fresh(self::RESPONSE_RELATIONS);
        });
    }

    /**
     * Approve the group loan.
     *
     * The group borrows as one loan, so there is exactly one calculation:
     * the service charge on the approved principal, and one monthly
     * installment over the group's term. No interest rate is involved at any
     * point — `amount_per_member` and each member's own monthly share are
     * presentation figures derived from these totals (see
     * GroupLoan::memberBreakdown()), never separate loans.
     */
    public function approve(GroupLoan $groupLoan, ?float $approvedAmountOverride, int $actorId, ?string $remarks): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $approvedAmountOverride, $actorId, $remarks) {
            $groupLoan = GroupLoan::lockForUpdate()->find($groupLoan->id);
            $application = $this->applicationFor($groupLoan);

            $approvedAmount = $approvedAmountOverride ?? (float) $groupLoan->requested_amount;

            $serviceCharge      = round($approvedAmount * (float) $groupLoan->service_charge_percentage / 100, 2);
            $totalRepayment     = round($approvedAmount + $serviceCharge, 2);
            $monthlyInstallment = round($totalRepayment / $groupLoan->term_months, 2);

            $memberCount     = max(1, $application->loanApplicationCustomers()->count());
            $amountPerMember = round($totalRepayment / $memberCount, 2);

            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Locked, $actorId, $remarks, [
                'approved_by'            => $actorId,
                'approved_at'            => now(),
                'approved_amount'        => $approvedAmount,
                'number_of_members'      => $memberCount,
                'service_charge_amount'  => $serviceCharge,
                'total_repayment_amount' => $totalRepayment,
                'amount_per_member'      => $amountPerMember,
                'approval_remarks'       => $remarks,
            ]);

            $this->loanApplicationWorkflowService->transition(
                $application,
                LoanApplicationStatus::Approved,
                $actorId,
                $remarks,
                [
                    'approved_by'         => $actorId,
                    'approved_at'         => now(),
                    'approved_amount'     => $approvedAmount,
                    'monthly_installment' => $monthlyInstallment,
                ]
            );

            return $groupLoan->fresh(self::RESPONSE_RELATIONS);
        });
    }

    /**
     * Reject the group loan and cascade the same terminal status to its
     * application. The application row is kept, not deleted.
     */
    public function reject(GroupLoan $groupLoan, string $rejectionReason, int $actorId): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $rejectionReason, $actorId) {
            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Rejected, $actorId, $rejectionReason, [
                'rejection_reason' => $rejectionReason,
            ]);

            $application = $this->applicationFor($groupLoan);

            if ($application->status->canTransitionTo(LoanApplicationStatus::Rejected)) {
                $this->loanApplicationWorkflowService->transition(
                    $application,
                    LoanApplicationStatus::Rejected,
                    $actorId,
                    $rejectionReason,
                    ['rejection_reason' => $rejectionReason]
                );
            }

            return $groupLoan->fresh(self::RESPONSE_RELATIONS);
        });
    }

    /**
     * Record the group's answer to an approved offer.
     *
     * Approving 400,000 against a request for 500,000 is an offer, not a
     * conclusion -- and that is as true of a group as of one borrower. Until
     * the group answers, the file waits: On Hold while they think, Accepted
     * when they agree, or Declined, which carries the reason and takes the
     * loan on to Cancelled.
     *
     * The answer is recorded on the application, not on the header. The header
     * is deliberately coarse -- Available, Locked, Disbursed, Closed -- and an
     * offer awaiting an answer is still a Locked group loan, exactly as an
     * approved one is. Only a decline moves the header, because that ends it.
     *
     * Without this step a group loan could never legitimately be disbursed:
     * Approved -> Disbursed is not a transition the state machine allows, so
     * disburse() threw for every group loan that reached it.
     */
    public function respondToOffer(
        GroupLoan $groupLoan,
        LoanApplicationStatus $to,
        int $actorId,
        ?string $remarks,
        ?string $declineReason = null
    ): GroupLoan {
        return DB::transaction(function () use ($groupLoan, $to, $actorId, $remarks, $declineReason) {
            $groupLoan = GroupLoan::lockForUpdate()->find($groupLoan->id);

            if ($groupLoan->status !== GroupLoanStatus::Locked) {
                throw new InvalidLoanApplicationTransitionException(
                    "Only an approved group loan has an offer to answer; this one is '{$groupLoan->status->value}'."
                );
            }

            $application = $this->applicationFor($groupLoan);

            $application = $this->loanApplicationWorkflowService->transition(
                $application,
                $to,
                $actorId,
                $remarks,
                [
                    'offer_responded_by'   => $actorId,
                    'offer_responded_at'   => now(),
                    'offer_remarks'        => $remarks,
                    'offer_decline_reason' => $to === LoanApplicationStatus::Declined ? $declineReason : null,
                ]
            );

            // A declined offer is the end of this group loan. Cancelling it
            // here rather than leaving it for someone to notice keeps the
            // pipeline honest, and both steps land in the status history.
            if ($to === LoanApplicationStatus::Declined) {
                $this->loanApplicationWorkflowService->transition(
                    $application,
                    LoanApplicationStatus::Cancelled,
                    $actorId,
                    'Cancelled: the group declined the approved offer'
                );

                // No cancelled_at column on group_loans, and cancel() does not
                // invent one either -- the status and its history row are the
                // record.
                $groupLoan = $this->transition(
                    $groupLoan,
                    GroupLoanStatus::Cancelled,
                    $actorId,
                    'Cancelled: the group declined the approved offer'
                );
            }

            return $groupLoan->fresh(self::RESPONSE_RELATIONS);
        });
    }

    /**
     * Disburse the group loan: the two-step Disbursed -> Active transition on
     * its application is what fires InstallmentScheduleService::generate(),
     * producing one schedule for the whole group. The header itself stops at
     * Disbursed — it has no Active state, since "money is out and the group is
     * repaying" is the same lifecycle tab either way.
     */
    public function disburse(GroupLoan $groupLoan, int $actorId): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $actorId) {
            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Disbursed, $actorId, null, [
                'disbursed_at' => now(),
            ]);

            $application = $this->applicationFor($groupLoan);

            // Money moves only after the group has said yes. The state machine
            // enforces this too (Approved has no edge to Disbursed), but the
            // message it would give names a status, not the thing that is
            // actually missing.
            if ($application->status !== LoanApplicationStatus::Accepted) {
                throw new InvalidLoanApplicationTransitionException(
                    'The group has not accepted the approved offer yet, so this loan cannot be disbursed.'
                );
            }

            $application = $this->loanApplicationWorkflowService->transition(
                $application,
                LoanApplicationStatus::Disbursed,
                $actorId,
                'Group loan disbursed to the group',
                ['disbursed_at' => now()]
            );

            $this->loanApplicationWorkflowService->transition(
                $application,
                LoanApplicationStatus::Active,
                $actorId,
                'Group loan activated on disbursement'
            );

            return $groupLoan->fresh(self::RESPONSE_RELATIONS);
        });
    }

    /**
     * Close the group loan once its loan has been repaid in full. Called from
     * PaymentController after a payment closes the loan; a no-op while the
     * loan is still being repaid, so it is safe to call after every payment.
     */
    public function closeIfFullyRepaid(GroupLoan $groupLoan, ?int $actorId = null): GroupLoan
    {
        if ($groupLoan->status !== GroupLoanStatus::Disbursed) {
            return $groupLoan;
        }

        $isOpen = $groupLoan->loanApplication()
            ->where('status', '!=', LoanApplicationStatus::Closed->value)
            ->exists();

        if ($isOpen) {
            return $groupLoan;
        }

        return $this->transition($groupLoan, GroupLoanStatus::Closed, $actorId);
    }

    /**
     * Cancel the group loan and cascade to its application if still cancellable.
     */
    public function cancel(GroupLoan $groupLoan, ?int $actorId, ?string $remarks): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $actorId, $remarks) {
            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Cancelled, $actorId, $remarks);

            $application = $this->applicationFor($groupLoan);

            if ($application->status->canTransitionTo(LoanApplicationStatus::Cancelled)) {
                $this->loanApplicationWorkflowService->transition(
                    $application,
                    LoanApplicationStatus::Cancelled,
                    $actorId,
                    $remarks
                );
            }

            return $groupLoan->fresh(self::RESPONSE_RELATIONS);
        });
    }
}
