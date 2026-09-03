<?php

namespace App\Services;

use App\Models\GroupLoan;

class GroupLoanMemberService
{
    /**
     * The minimum a group loan can shrink to, mirroring
     * CreateGroupLoanRequest's `number_of_members` => min:2. Hardcoded rather
     * than a System Setting, consistent with how the rest of the group loan
     * rules are fixed.
     */
    public const MIN_MEMBERS = 2;

    /**
     * Bring the header and every member row back in step after the member
     * list or the requested amount changed: renumber the members from 1,
     * write the resulting count onto the header, and re-split the requested
     * amount evenly (last member absorbs the rounding remainder, exactly as
     * GroupLoanController::store() and GroupLoanWorkflowService::approve()
     * both do).
     *
     * Only meaningful before approval — after that the amounts are frozen and
     * the callers all guard on GroupLoanStatus::Available first.
     */
    public function resync(GroupLoan $groupLoan): GroupLoan
    {
        $members = $groupLoan->memberLoanApplications()
            ->lockForUpdate()
            ->orderBy('group_member_no')
            ->orderBy('id')
            ->get();

        $memberCount = $members->count();

        if ($memberCount === 0) {
            return $groupLoan;
        }

        $requestedAmount = (float) $groupLoan->requested_amount;
        $share = round($requestedAmount / $memberCount, 2);
        $runningTotal = 0.0;

        foreach ($members as $index => $member) {
            $isLast = $index === $memberCount - 1;
            $memberAmount = $isLast
                ? round($requestedAmount - $runningTotal, 2)
                : $share;
            $runningTotal += $memberAmount;

            $member->update([
                'group_member_no'  => $index + 1,
                'requested_amount' => $memberAmount,
            ]);

            // The generic applications row mirrors the member's own amount.
            $member->application?->update(['requested_amount' => $memberAmount]);
        }

        $groupLoan->update(['number_of_members' => $memberCount]);

        return $groupLoan->refresh();
    }
}
