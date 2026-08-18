<?php

namespace App\Services;

use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRevision;
use Carbon\Carbon;

class InstallmentScheduleService
{
    /**
     * Generate the installment schedule for a disbursed loan application,
     * spreading the stored monthly_installment across term_months with the
     * last installment absorbing any rounding remainder.
     */
    public function generate(LoanApplication $loanApplication): void
    {
        if ($loanApplication->installments()->exists()) {
            return;
        }

        $termMonths = $loanApplication->term_months;
        $monthlyInstallment = round($loanApplication->monthly_installment, 2);
        $baseDate = $loanApplication->disbursed_at ?? now();

        $totalPayable = round($monthlyInstallment * $termMonths, 2);
        $runningTotal = 0;

        for ($installmentNo = 1; $installmentNo <= $termMonths; $installmentNo++) {
            $isLastInstallment = $installmentNo === $termMonths;
            $amountDue = $isLastInstallment
                ? round($totalPayable - $runningTotal, 2)
                : $monthlyInstallment;
            $runningTotal += $amountDue;

            LoanInstallment::create([
                'loan_application_id' => $loanApplication->id,
                'installment_no'      => $installmentNo,
                'due_date'             => $baseDate->copy()->addMonths($installmentNo)->toDateString(),
                'amount_due'           => $amountDue,
                'amount_paid'          => 0,
                'penalty_amount'       => 0,
                'balance'               => $amountDue,
                'status'                => 'upcoming',
            ]);
        }

        $loanApplication->update(['outstanding_balance' => $totalPayable]);
    }

    /**
     * Generate the installment schedule produced by an approved loan revision,
     * spreading its revised_outstanding_amount across revised_term the same way
     * generate() does, but continuing the loan's existing due-date cadence and
     * installment numbering instead of restarting either from scratch.
     */
    public function generateFromRevision(LoanApplication $loanApplication, LoanRevision $revision): void
    {
        $termMonths = $revision->revised_term;
        $monthlyInstallment = round($revision->revised_installment_amount, 2);
        $totalPayable = round($revision->revised_outstanding_amount, 2);

        $lastDueDate = $loanApplication->installments()->max('due_date');
        $baseDate = $lastDueDate ? Carbon::parse($lastDueDate) : now();

        $startingNo = (int) $loanApplication->installments()->max('installment_no');

        $runningTotal = 0;

        for ($i = 1; $i <= $termMonths; $i++) {
            $isLastInstallment = $i === $termMonths;
            $amountDue = $isLastInstallment
                ? round($totalPayable - $runningTotal, 2)
                : $monthlyInstallment;
            $runningTotal += $amountDue;

            LoanInstallment::create([
                'loan_application_id' => $loanApplication->id,
                'loan_revision_id'    => $revision->id,
                'installment_no'      => $startingNo + $i,
                'due_date'             => $baseDate->copy()->addMonths($i)->toDateString(),
                'amount_due'           => $amountDue,
                'amount_paid'          => 0,
                'penalty_amount'       => 0,
                'balance'               => $amountDue,
                'status'                => 'upcoming',
            ]);
        }
    }
}
