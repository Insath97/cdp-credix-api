<?php

namespace App\Services;

use App\Models\GroupLoan;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRevision;
use Carbon\Carbon;

class InstallmentScheduleService
{
    /**
     * Generate the installment schedule for a disbursed loan application.
     *
     * An Individual or Joint Loan gets one schedule: the stored
     * monthly_installment spread across term_months, last installment
     * absorbing the rounding remainder.
     *
     * A Group Loan gets one schedule **per member**. The group borrows as one
     * loan, but each member owes their own share of every period, so each gets
     * their own rows carrying their own amount_due/amount_paid/balance/
     * penalty_amount/status. That is what lets one member fall behind while the
     * others stay paid — and lets overdue notices reach only the member who
     * actually missed a payment.
     */
    public function generate(LoanApplication $loanApplication): void
    {
        if ($loanApplication->installments()->exists()) {
            return;
        }

        $termMonths = $loanApplication->term_months;
        $monthlyInstallment = round($loanApplication->monthly_installment, 2);
        $baseDate = $loanApplication->disbursed_at ?? now();

        $owners = $this->scheduleOwnersFor($loanApplication);
        $monthlyShares = GroupLoan::splitEvenly($monthlyInstallment, count($owners));

        // Split the loan's REAL total, not the rounded installment times the
        // term. The total used to be rebuilt as round($share * $termMonths, 2),
        // which is derived from a figure that has already been rounded to the
        // cent, so it drifted from what the borrower was actually approved for:
        // 100,000 at flat 21% over 12 months owes 121,000.00 but scheduled
        // 120,999.96, and outstanding_balance was then written as that, leaving
        // a loan that could never be closed at the approved figure. It also made
        // writeSchedule()'s "last installment absorbs the remainder" a no-op,
        // because there was no remainder left to absorb.
        $totalShares = GroupLoan::splitEvenly($this->totalPayableFor($loanApplication), count($owners));

        $grandTotal = 0.0;

        foreach ($owners as $index => $customerId) {
            $grandTotal += $this->writeSchedule(
                $loanApplication,
                $customerId,
                $monthlyShares[$index],
                $totalShares[$index],
                $termMonths,
                $baseDate,
                0,
                null
            );
        }

        $loanApplication->update(['outstanding_balance' => round($grandTotal, 2)]);
    }

    /**
     * Generate the installment schedule produced by an approved loan revision,
     * spreading its revised_outstanding_amount across revised_term the same way
     * generate() does, but continuing the loan's existing due-date cadence and
     * installment numbering instead of restarting either from scratch.
     *
     * Group loans keep their per-member split here too: every member shares one
     * installment_no sequence, so max('installment_no') is the same for all of
     * them and the revision's rows continue from it for each member.
     */
    public function generateFromRevision(LoanApplication $loanApplication, LoanRevision $revision): void
    {
        $termMonths = $revision->revised_term;
        $monthlyInstallment = round($revision->revised_installment_amount, 2);
        $totalPayable = round($revision->revised_outstanding_amount, 2);

        $lastDueDate = $loanApplication->installments()->max('due_date');
        $baseDate = $lastDueDate ? Carbon::parse($lastDueDate) : now();

        $startingNo = (int) $loanApplication->installments()->max('installment_no');

        $owners = $this->scheduleOwnersFor($loanApplication);
        $ownerCount = count($owners);
        $monthlyShares = GroupLoan::splitEvenly($monthlyInstallment, $ownerCount);
        $totalShares = GroupLoan::splitEvenly($totalPayable, $ownerCount);

        foreach ($owners as $index => $customerId) {
            $this->writeSchedule(
                $loanApplication,
                $customerId,
                $monthlyShares[$index],
                $totalShares[$index],
                $termMonths,
                $baseDate,
                $startingNo,
                $revision->id
            );
        }
    }

    /**
     * Who owes a schedule on this loan.
     *
     * A Group Loan's members each owe their own share, so there is one owner
     * per attached customer. Everything else — Individual and Joint Loans —
     * has exactly one owner, the loan's own customer, which makes the loop in
     * generate() collapse to precisely the single-schedule behaviour that was
     * here before per-member installments existed.
     *
     * @return array<int, int|null>
     */
    private function scheduleOwnersFor(LoanApplication $loanApplication): array
    {
        if ($loanApplication->group_loan_id !== null) {
            $memberIds = $loanApplication->loanApplicationCustomers()
                ->orderBy('id')
                ->pluck('customer_id')
                ->filter()
                ->values()
                ->all();

            if (!empty($memberIds)) {
                return $memberIds;
            }
        }

        return [$loanApplication->customer_id];
    }

    /**
     * Write one owner's run of installments, the last absorbing whatever the
     * rounding left over so the rows always add back up to $totalPayable.
     *
     * @return float The total actually written, for the caller to aggregate.
     */
    /**
     * What this loan actually owes over its whole term.
     *
     * The two lending shapes arrive at it differently. A group loan borrows as
     * one loan and carries its own total_repayment_amount, struck when the
     * group was approved. An individual or joint loan is the approved principal
     * plus the interest computed from it, using the same expression approve()
     * used to derive monthly_installment in the first place.
     *
     * Falls back to the rounded installment times the term when neither is
     * available, which is the figure this method replaced: a loan disbursed
     * without an approved amount is already in a strange state, and a schedule
     * that is a few cents out beats no schedule at all.
     */
    private function totalPayableFor(LoanApplication $loanApplication): float
    {
        if ($loanApplication->isGroupLoan()) {
            $groupTotal = $loanApplication->groupLoan?->total_repayment_amount;

            if ($groupTotal !== null) {
                return round((float) $groupTotal, 2);
            }
        }

        $approved = $loanApplication->approved_amount;

        if ($approved !== null) {
            $interest = round((float) $approved * (float) $loanApplication->interest_rate / 100, 2);

            return round((float) $approved + $interest, 2);
        }

        return round((float) $loanApplication->monthly_installment * $loanApplication->term_months, 2);
    }

    private function writeSchedule(
        LoanApplication $loanApplication,
        ?int $customerId,
        float $monthlyInstallment,
        float $totalPayable,
        int $termMonths,
        Carbon $baseDate,
        int $startingNo,
        ?int $revisionId
    ): float {
        $runningTotal = 0;

        for ($offset = 1; $offset <= $termMonths; $offset++) {
            $isLastInstallment = $offset === $termMonths;
            $amountDue = $isLastInstallment
                ? round($totalPayable - $runningTotal, 2)
                : $monthlyInstallment;
            $runningTotal += $amountDue;

            LoanInstallment::create([
                'loan_application_id' => $loanApplication->id,
                'customer_id'         => $customerId,
                'loan_revision_id'    => $revisionId,
                'installment_no'      => $startingNo + $offset,
                'due_date'            => $baseDate->copy()->addMonths($offset)->toDateString(),
                'amount_due'          => $amountDue,
                'amount_paid'         => 0,
                'penalty_amount'      => 0,
                'balance'             => $amountDue,
                'status'              => 'upcoming',
            ]);
        }

        return round($runningTotal, 2);
    }
}
