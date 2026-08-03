<?php

namespace App\Services;

use App\Models\LoanApplication;
use App\Models\LoanInstallment;

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
    }
}
