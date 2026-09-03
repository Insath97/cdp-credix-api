<?php

namespace App\Services;

use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Models\GroupLoan;
use Illuminate\Support\Facades\DB;

class GroupLoanWorkflowService
{
    public function __construct(protected LoanApplicationWorkflowService $loanApplicationWorkflowService)
    {
    }

    /**
     * Transition the group loan header to a new lifecycle status, guarded by
     * GroupLoanStatus' own transition map. Unlike
     * LoanApplicationWorkflowService::transition(), this does not open its
     * own DB transaction — callers (verify/approve/disburse/reject/cancel)
     * already wrap the full cascade in one — and there is no group-level
     * status history write; the cascaded per-member transitions already
     * record the timeline via loan_application_status_history.
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

        return $groupLoan->fresh([
            'loanProduct',
            'branch',
            'items',
            'memberLoanApplications.customer',
            'appliedByUser',
            'reviewedByUser',
            'approvedByUser',
        ]);
    }

    /**
     * Verify the group loan and cascade the Verified transition to every
     * member. The header status is deliberately left at Available: a
     * submitted and a verified group loan are both still "open" as far as
     * the lifecycle tabs are concerned, so only the review audit fields move
     * here. Reaching Verified is still enforced — the per-member cascade
     * below runs through LoanApplicationStatus' own guard.
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

            $groupLoan->refresh()->load('memberLoanApplications');

            foreach ($groupLoan->memberLoanApplications as $member) {
                $this->loanApplicationWorkflowService->transition(
                    $member,
                    LoanApplicationStatus::Verified,
                    $actorId,
                    $remarks,
                    $extra
                );
            }

            return $groupLoan->fresh(['memberLoanApplications.customer']);
        });
    }

    /**
     * Approve the group loan: split the approved principal evenly across
     * members (last member absorbs the rounding remainder), then let each
     * member's own copied-down interest_rate (which, for a group-loan
     * member row, holds the service charge percentage snapshotted at
     * submission) and term_months independently derive their own service
     * charge/monthly_installment via the exact same formula
     * LoanApplicationController::approve() already uses. Splitting the
     * principal (not the total repayment) is what avoids double-applying
     * the service charge.
     */
    public function approve(GroupLoan $groupLoan, ?float $approvedAmountOverride, int $actorId, ?string $remarks): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $approvedAmountOverride, $actorId, $remarks) {
            $groupLoan = GroupLoan::lockForUpdate()->find($groupLoan->id);

            $approvedAmount = $approvedAmountOverride ?? (float) $groupLoan->requested_amount;

            $serviceCharge = round($approvedAmount * $groupLoan->service_charge_percentage / 100, 2);
            $totalRepayment = round($approvedAmount + $serviceCharge, 2);
            $amountPerMember = round($totalRepayment / $groupLoan->number_of_members, 2);

            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Locked, $actorId, $remarks, [
                'approved_by'            => $actorId,
                'approved_at'            => now(),
                'approved_amount'        => $approvedAmount,
                'service_charge_amount'  => $serviceCharge,
                'total_repayment_amount' => $totalRepayment,
                'amount_per_member'      => $amountPerMember,
                'approval_remarks'       => $remarks,
            ]);

            $members = $groupLoan->memberLoanApplications()->lockForUpdate()->orderBy('group_member_no')->get();
            $memberCount = $members->count();
            $principalPerMember = round($approvedAmount / $memberCount, 2);
            $runningPrincipal = 0;

            foreach ($members as $index => $member) {
                $isLast = $index === $memberCount - 1;
                $memberPrincipal = $isLast
                    ? round($approvedAmount - $runningPrincipal, 2)
                    : $principalPerMember;
                $runningPrincipal += $memberPrincipal;

                $memberServiceCharge = round($memberPrincipal * $member->interest_rate / 100, 2);
                $memberTotalRepayment = round($memberPrincipal + $memberServiceCharge, 2);
                $memberMonthlyInstallment = round($memberTotalRepayment / $member->term_months, 2);

                $this->loanApplicationWorkflowService->transition(
                    $member,
                    LoanApplicationStatus::Approved,
                    $actorId,
                    $remarks,
                    [
                        'approved_by'         => $actorId,
                        'approved_at'         => now(),
                        'approved_amount'     => $memberPrincipal,
                        'monthly_installment' => $memberMonthlyInstallment,
                    ]
                );
            }

            return $groupLoan->fresh(['memberLoanApplications.customer', 'memberLoanApplications.installments']);
        });
    }

    /**
     * Reject the group loan and cascade the same terminal status to every
     * member still in a rejectable state. Member rows are kept, not deleted.
     */
    public function reject(GroupLoan $groupLoan, string $rejectionReason, int $actorId): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $rejectionReason, $actorId) {
            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Rejected, $actorId, $rejectionReason, [
                'rejection_reason' => $rejectionReason,
            ]);

            foreach ($groupLoan->memberLoanApplications as $member) {
                if ($member->status->canTransitionTo(LoanApplicationStatus::Rejected)) {
                    $this->loanApplicationWorkflowService->transition(
                        $member,
                        LoanApplicationStatus::Rejected,
                        $actorId,
                        $rejectionReason,
                        ['rejection_reason' => $rejectionReason]
                    );
                }
            }

            return $groupLoan->fresh(['memberLoanApplications.customer']);
        });
    }

    /**
     * Disburse the group loan: cascade the two-step Disbursed -> Active
     * transition to every member, which is what fires
     * InstallmentScheduleService::generate() per member automatically, with
     * zero changes to that service. The header itself stops at Disbursed —
     * it has no Active state, since "money is out and members are repaying"
     * is the same lifecycle tab either way.
     */
    public function disburse(GroupLoan $groupLoan, int $actorId): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $actorId) {
            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Disbursed, $actorId, null, [
                'disbursed_at' => now(),
            ]);

            foreach ($groupLoan->memberLoanApplications as $member) {
                $member = $this->loanApplicationWorkflowService->transition(
                    $member,
                    LoanApplicationStatus::Disbursed,
                    $actorId,
                    null,
                    ['disbursed_at' => now()]
                );

                $this->loanApplicationWorkflowService->transition(
                    $member,
                    LoanApplicationStatus::Active,
                    $actorId
                );
            }

            return $groupLoan->fresh(['memberLoanApplications.customer', 'memberLoanApplications.installments']);
        });
    }

    /**
     * Close the group loan once every member's own loan has been repaid in
     * full. Called from PaymentController after the payment that closes the
     * last outstanding member loan; a no-op while any member is still
     * repaying, so it is safe to call after every group-member payment.
     */
    public function closeIfFullyRepaid(GroupLoan $groupLoan, ?int $actorId = null): GroupLoan
    {
        if ($groupLoan->status !== GroupLoanStatus::Disbursed) {
            return $groupLoan;
        }

        $hasOpenMember = $groupLoan->memberLoanApplications()
            ->where('status', '!=', LoanApplicationStatus::Closed->value)
            ->exists();

        if ($hasOpenMember) {
            return $groupLoan;
        }

        return $this->transition($groupLoan, GroupLoanStatus::Closed, $actorId);
    }

    /**
     * Cancel the group loan and cascade to every member still cancellable.
     */
    public function cancel(GroupLoan $groupLoan, ?int $actorId, ?string $remarks): GroupLoan
    {
        return DB::transaction(function () use ($groupLoan, $actorId, $remarks) {
            $groupLoan = $this->transition($groupLoan, GroupLoanStatus::Cancelled, $actorId, $remarks);

            foreach ($groupLoan->memberLoanApplications as $member) {
                if ($member->status->canTransitionTo(LoanApplicationStatus::Cancelled)) {
                    $this->loanApplicationWorkflowService->transition(
                        $member,
                        LoanApplicationStatus::Cancelled,
                        $actorId,
                        $remarks
                    );
                }
            }

            return $groupLoan->fresh(['memberLoanApplications.customer']);
        });
    }
}
