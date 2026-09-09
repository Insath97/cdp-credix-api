<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\CustomerCreditScore;
use App\Models\LoanApplication;
use App\Services\CreditScoreService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;

class RecomputeCreditScores extends Command
{
    use ActivityLogTrait;

    /**
     * --all rescores every loan that ever had a schedule, including closed
     * ones. That is the backfill: run it once after deploying the feature so
     * existing borrowers arrive with the history they actually have, rather
     * than every one of them looking like a first-time applicant.
     */
    protected $signature = 'credit-score:recompute
                            {--all : Rescore every loan with a schedule, not just the live ones (use for backfill)}
                            {--customer= : Rescore only this customer id}
                            {--loan= : Rescore only this loan application id}';

    protected $description = 'Rebuild repayment credit scores from the installment schedule and roll them up onto each customer.';

    public function handle(CreditScoreService $creditScoreService): int
    {
        if (!$creditScoreService->enabled()) {
            $this->warn('Credit scoring is disabled (credit_score_enabled). Nothing recomputed.');

            return self::SUCCESS;
        }

        if ($customerId = $this->option('customer')) {
            $scores = $creditScoreService->recomputeForCustomer((int) $customerId);
            $this->info("Customer {$customerId}: {$scores->count()} loan score(s) rebuilt.");

            return self::SUCCESS;
        }

        $query = LoanApplication::query();

        if ($loanId = $this->option('loan')) {
            $query->whereKey($loanId);
        } elseif (!$this->option('all')) {
            // The nightly default. A loan that is still repaying is the only
            // one whose score can change on its own: an unpaid installment
            // crosses its grace period as the clock moves, with no request to
            // hang a recompute off. Everything else only changes when someone
            // touches it, and those paths recompute inline.
            $query->whereIn('status', [
                LoanApplicationStatus::Active,
                LoanApplicationStatus::Overdue,
            ]);
        }

        // has('installments') keeps a loan that was never disbursed out of the
        // roll-up entirely, so an applicant cannot end up with a score row of
        // zero judged installments.
        $query->has('installments');

        $loansProcessed = 0;
        $scoresWritten = 0;
        $failures = 0;

        // Chunked because --all walks the whole book, and each loan pulls its
        // full schedule into memory to judge it.
        $query->orderBy('id')->chunkById(100, function ($loanApplications) use (
            $creditScoreService,
            &$loansProcessed,
            &$scoresWritten,
            &$failures
        ) {
            foreach ($loanApplications as $loanApplication) {
                try {
                    $scoresWritten += $creditScoreService->recomputeForLoan($loanApplication)->count();
                    $loansProcessed++;
                } catch (\Throwable $th) {
                    // One malformed loan must not abandon the rest of the book.
                    $failures++;
                    $this->error("Failed to score loan application ID {$loanApplication->id}: {$th->getMessage()}");
                }
            }
        });

        // --all is the "rebuild everything" mode, so it also has to remove what
        // should no longer exist. A loan that lost its schedule (or was scored
        // before the has('installments') guard existed) is skipped by the loop
        // above, so its stale row would otherwise survive every rebuild and
        // keep padding the customer's history with a loan that has nothing to
        // say about how they repay.
        $pruned = 0;
        if ($this->option('all') && !$this->option('loan')) {
            $pruned = CustomerCreditScore::whereDoesntHave('loanApplication.installments')->delete();

            if ($pruned > 0) {
                // The aggregate is a weighted average of those rows, so anyone
                // who lost one has to be re-rolled up.
                foreach (Customer::whereNotNull('credit_score')->pluck('id') as $customerId) {
                    $creditScoreService->recomputeCustomerAggregate((int) $customerId);
                }
            }
        }

        $summary = "Loans processed: {$loansProcessed}. Customer scores written: {$scoresWritten}. Stale rows pruned: {$pruned}. Failures: {$failures}.";
        $this->info($summary);

        $this->logActivity('UPDATE', 'CustomerCreditScore', $summary, [
            'loans_processed' => $loansProcessed,
            'scores_written'  => $scoresWritten,
            'stale_pruned'    => $pruned,
            'failures'        => $failures,
            'mode'            => $this->option('all') ? 'all' : 'live',
        ]);

        return self::SUCCESS;
    }
}
