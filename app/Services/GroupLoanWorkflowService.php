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
        return DB::transaction(function () use ($groupLoan, $actorId, $remarks, $extra) {
            if ($groupLoan->status !== GroupLoanStatus::Available) {
                throw new InvalidLoanApplicationTransitionException(
                    "Cannot verify a group loan that is '{$groupLoan->status->value}'."
                );
            }

            if (!empty($extra)) {
                $groupLoan->update($extra);
            }

            $groupLoan->refresh();

            $this->loanApplicationWorkflowService->transition(
                $this->applicationFor($groupLoan),
                LoanApplicationStatus::Verified,
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

            $application = $this->loanApplicationWorkflowService->transition(
                $application,
                LoanApplicationStatus::Disbursed,
                $actorId,
                null,
                ['disbursed_at' => now()]
            );

            $this->loanApplicationWorkflowService->transition(
                $application,
                LoanApplicationStatus::Active,
                $actorId
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
