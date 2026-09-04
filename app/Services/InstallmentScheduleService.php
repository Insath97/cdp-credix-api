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

        $grandTotal = 0.0;

        foreach ($owners as $index => $customerId) {
            $share = $monthlyShares[$index];

            $grandTotal += $this->writeSchedule(
                $loanApplication,
                $customerId,
                $share,
                round($share * $termMonths, 2),
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
