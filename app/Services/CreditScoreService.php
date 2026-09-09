<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Models\CreditScoreEvent;
use App\Models\Customer;
use App\Models\CustomerCreditScore;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repayment credit scoring.
 *
 * The rule the business asked for: every installment settled on time earns the
 * borrower points, every one settled late (or left unpaid past its grace
 * period) costs them points, and the loan's score is the total divided by the
 * number of installments judged -- so a 6-month loan and a 36-month loan are
 * directly comparable. All four numbers in that sentence are System Settings,
 * not constants.
 *
 * ## Recompute, never increment
 *
 * Every figure this service writes is DERIVED from loan_installments, and is
 * rebuilt from scratch on each call rather than adjusted by a delta. That is
 * deliberate and it is the whole design:
 *
 *   - PaymentController::destroy() reverses a receipt and rewinds the
 *     installment's status and paid_at;
 *   - LoanInstallmentController::update() waives a penalty or edits a row;
 *   - LoanRevisionService restructures a loan and stamps old rows 'revised';
 *   - the nightly scheduler stamps rows 'overdue' long after the fact.
 *
 * An incremental +10/-10 ledger would drift out of step with every one of
 * those and there would be no way to tell that it had. Recomputing means the
 * ledger cannot be wrong for longer than it takes the next recompute to run,
 * and it means the backfill command and the live path share one code path.
 *
 * Consequently: nothing outside this service may write to credit_score_events,
 * customer_credit_scores, or customers.credit_score.
 */
class CreditScoreService
{
    /**
     * Installment statuses that are excluded from scoring entirely.
     *
     * 'revised' rows were superseded by a restructure -- the replacement rows
     * carry the real schedule, and counting both would judge the borrower
     * twice for one month. 'waived' rows were written off by management, which
     * is a decision about the debt, not evidence about the borrower.
     */
    public const UNSCORED_STATUSES = ['revised', 'waived'];

    /**
     * config() memoised for the life of this instance.
     *
     * The service is bound as a singleton (AppServiceProvider) precisely so
     * this survives a whole request. Customer::getCreditScoreBandAttribute()
     * calls band() once per serialised customer, and CACHE_STORE is `database`
     * here -- without this, listing 15 customers would fire 75 cache queries
     * for five values that cannot change mid-request.
     */
    protected ?array $config = null;

    /**
     * Whether scoring is switched on.
     *
     * When off, every entry point returns without writing. Scores already
     * stored are deliberately left in place rather than nulled: the switch is
     * meant to pause an expensive nightly job, not to destroy history.
     */
    public function enabled(): bool
    {
        return (bool) Setting::get('credit_score_enabled', true);
    }

    /**
     * The tuning knobs, read once per operation.
     *
     * Setting::get() is cached forever and busted on update, so this is cheap;
     * reading them once still matters, because it guarantees every installment
     * in a single recompute is judged by the same rules even if an admin saves
     * a settings change mid-run.
     */
    public function config(): array
    {
        return $this->config ??= [
            'on_time_points'       => (float) Setting::get('credit_score_on_time_points', 10),
            'late_penalty_points'  => (float) Setting::get('credit_score_late_penalty_points', 10),
            'grace_days'           => (int) Setting::get('credit_score_grace_days', 0),
            'count_unpaid_overdue' => (bool) Setting::get('credit_score_count_unpaid_overdue', true),
            'normalize_max'        => (int) Setting::get('credit_score_normalize_max', 100),
        ];
    }

    /**
     * Drop the memoised config so the next read picks up changed settings.
     *
     * Only needed where the settings change inside the same process that then
     * rescores -- SettingController::update() does exactly that when an admin
     * saves and the UI refreshes, and the verification scripts rely on it too.
     */
    public function forgetConfig(): void
    {
        $this->config = null;
    }

    /**
     * Judge one installment.
     *
     * Returns null when the installment says nothing about the borrower yet --
     * it is excluded by status, it has no due date, or it is still inside its
     * window with time left to pay. Those rows are not counted in the divisor
     * either, so an unpaid month that is not yet late cannot drag the average
     * down.
     *
     * @return array{event_type:string,points:float,days_late:int,occurred_on:\Carbon\CarbonInterface,remarks:string}|null
     */
    public function evaluateInstallment(LoanInstallment $installment, array $config): ?array
    {
        if (in_array($installment->status, self::UNSCORED_STATUSES, true)) {
            return null;
        }

        if (!$installment->due_date) {
            return null;
        }

        // The credit score's own grace period, deliberately separate from the
        // product's recovery grace period. Recovery grace decides when to
        // charge a late fee and chase the borrower; this decides when a payment
        // stops counting as punctual, and a lender may well want the second to
        // be stricter than the first.
        $deadline = $installment->due_date->copy()->startOfDay()->addDays($config['grace_days']);

        // 'paid' is the status stamp; balance <= 0 is the truth. Check both,
        // because MarkOverdueLoanApplications can stamp 'overdue' on a row
        // whose balance a correction has already cleared.
        $isSettled = $installment->status === 'paid' || (float) $installment->balance <= 0;

        if ($isSettled) {
            // paid_at is stamped the moment the balance reaches zero.
            // updated_at is the only fallback for a row settled by some path
            // that did not stamp it; without a date we cannot prove lateness,
            // and an unprovable case must not be charged against the borrower.
            $paidOn = ($installment->paid_at ?? $installment->updated_at)?->copy()->startOfDay();

            if (!$paidOn) {
                return null;
            }

            $daysLate = max(0, (int) $deadline->diffInDays($paidOn, false));

            if ($daysLate === 0) {
                return [
                    'event_type'  => CreditScoreEvent::TYPE_ON_TIME,
                    'points'      => $config['on_time_points'],
                    'days_late'   => 0,
                    'occurred_on' => $paidOn,
                    'remarks'     => "Installment {$installment->installment_no} settled on time.",
                ];
            }

            return [
                'event_type'  => CreditScoreEvent::TYPE_LATE_PAID,
                'points'      => -$config['late_penalty_points'],
                'days_late'   => $daysLate,
                'occurred_on' => $paidOn,
                'remarks'     => "Installment {$installment->installment_no} settled {$daysLate} day(s) late.",
            ];
        }

        // Still owing. Without the branch below a borrower who simply never
        // pays would never lose a single point -- the deduction would sit
        // waiting for a payment that never comes.
        if (!$config['count_unpaid_overdue']) {
            return null;
        }

        $today = now()->startOfDay();

        if ($deadline->gte($today)) {
            // Not late yet. No verdict, and not counted in the divisor.
            return null;
        }

        $daysLate = max(0, (int) $deadline->diffInDays($today, false));

        return [
            'event_type'  => CreditScoreEvent::TYPE_LATE_UNPAID,
            'points'      => -$config['late_penalty_points'],
            'days_late'   => $daysLate,
            'occurred_on' => $deadline,
            'remarks'     => "Installment {$installment->installment_no} unpaid {$daysLate} day(s) past its grace period.",
        ];
    }

    /**
     * Rebuild every score on one loan.
     *
     * @param  int|null  $onlyCustomerId  Limit to one group loan member -- the
     *         payment path knows exactly whose month was just settled and has
     *         no reason to re-judge the other members.
     * @return Collection<int, CustomerCreditScore>
     */
    public function recomputeForLoan(LoanApplication $loanApplication, ?int $onlyCustomerId = null): Collection
    {
        if (!$this->enabled()) {
            return collect();
        }

        $config = $this->config();
        $results = collect();

        foreach ($this->scoreOwnersFor($loanApplication) as $customerId) {
            if ($onlyCustomerId !== null && $customerId !== $onlyCustomerId) {
                continue;
            }

            $score = $this->recomputePair($loanApplication, $customerId, $config);

            if ($score) {
                $results->push($score);
                $this->recomputeCustomerAggregate($customerId, $config);
            }
        }

        return $results;
    }

    /**
     * Rebuild every score this customer has, across all their loans, and their
     * aggregate. Used by the backfill and by the on-demand refresh endpoint.
     */
    public function recomputeForCustomer(int $customerId): Collection
    {
        if (!$this->enabled()) {
            return collect();
        }

        $config = $this->config();
        $results = collect();

        $loanIds = LoanInstallment::where('customer_id', $customerId)
            ->distinct()
            ->pluck('loan_application_id');

        // An individual loan whose installments predate per-row customer
        // stamping carries no customer_id, so reach those through the loan.
        $loanIds = $loanIds->merge(
            LoanApplication::where('customer_id', $customerId)->pluck('id')
        )->unique();

        // has('installments') keeps loans that were never disbursed out of the
        // roll-up. Without it every application a customer has merely submitted
        // earns a score row of zero judged installments, and the officer
        // reading their history sees a list padded with "no history" loans that
        // say nothing about how they repay. Mirrors the same guard in
        // credit-score:recompute.
        foreach (LoanApplication::has('installments')->whereIn('id', $loanIds)->get() as $loanApplication) {
            if (!in_array($customerId, $this->scoreOwnersFor($loanApplication), true)) {
                continue;
            }

            $score = $this->recomputePair($loanApplication, $customerId, $config);

            if ($score) {
                $results->push($score);
            }
        }

        $this->recomputeCustomerAggregate($customerId, $config);

        return $results;
    }

    /**
     * Rebuild one (loan, customer) pair: its ledger and its roll-up row.
     *
     * Events are deleted and reinserted wholesale rather than reconciled one by
     * one, so an installment that has stopped being scorable (revised away,
     * penalty waived, payment reversed back inside its window) leaves nothing
     * behind. Kept in a transaction so a reader can never catch the ledger with
     * the old rows deleted and the new ones not yet written.
     */
    protected function recomputePair(LoanApplication $loanApplication, int $customerId, array $config): ?CustomerCreditScore
    {
        $installments = $this->installmentsFor($loanApplication, $customerId);

        // No schedule at all means the loan was never disbursed, so there is
        // nothing to judge and no row to keep. Deleting rather than writing a
        // zero row also cleans up after a loan whose schedule was removed --
        // otherwise a stale score would outlive the thing it scored.
        if ($installments->isEmpty()) {
            CreditScoreEvent::where('loan_application_id', $loanApplication->id)
                ->where('customer_id', $customerId)
                ->delete();

            CustomerCreditScore::where('loan_application_id', $loanApplication->id)
                ->where('customer_id', $customerId)
                ->delete();

            return null;
        }

        return DB::transaction(function () use ($loanApplication, $customerId, $config, $installments) {
            CreditScoreEvent::where('loan_application_id', $loanApplication->id)
                ->where('customer_id', $customerId)
                ->delete();

            $totalPoints = 0.0;
            $onTime = 0;
            $late = 0;
            $rows = [];

            foreach ($installments as $installment) {
                $verdict = $this->evaluateInstallment($installment, $config);

                if ($verdict === null) {
                    continue;
                }

                $totalPoints += $verdict['points'];

                if ($verdict['event_type'] === CreditScoreEvent::TYPE_ON_TIME) {
                    $onTime++;
                } else {
                    $late++;
                }

                $rows[] = [
                    'customer_id'         => $customerId,
                    'loan_application_id' => $loanApplication->id,
                    'loan_installment_id' => $installment->id,
                    'event_type'          => $verdict['event_type'],
                    'points'              => $verdict['points'],
                    'days_late'           => $verdict['days_late'],
                    'occurred_on'         => $verdict['occurred_on']->toDateString(),
                    'remarks'             => $verdict['remarks'],
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }

            if ($rows) {
                // One statement per chunk rather than per installment: a
                // 36-month loan recomputed on every receipt would otherwise be
                // 36 inserts inside the payment request.
                foreach (array_chunk($rows, 100) as $chunk) {
                    CreditScoreEvent::insert($chunk);
                }
            }

            $counted = $onTime + $late;
            $average = $counted > 0 ? round($totalPoints / $counted, 2) : null;

            return CustomerCreditScore::updateOrCreate(
                [
                    'loan_application_id' => $loanApplication->id,
                    'customer_id'         => $customerId,
                ],
                [
                    'total_points'         => round($totalPoints, 2),
                    'installments_counted' => $counted,
                    'on_time_count'        => $onTime,
                    'late_count'           => $late,
                    'average_points'       => $average,
                    'final_score'          => $this->normalize($average, $config),
                    'computed_at'          => now(),
                    // Stamped only once the loan is fully repaid. Cleared again
                    // if it somehow leaves Closed, so the flag can never claim
                    // a verdict is final while months are still being judged.
                    'finalized_at'         => $loanApplication->status === LoanApplicationStatus::Closed ? now() : null,
                ]
            );
        });
    }

    /**
     * Roll every loan score a customer has into the single figure a loan
     * officer reads at application time, and denormalise it onto the customer.
     *
     * Weighted by installments counted rather than a plain mean of the per-loan
     * scores: 24 punctual months on a long loan should not be cancelled out by
     * one late month on a 3-month top-up.
     */
    public function recomputeCustomerAggregate(int $customerId, ?array $config = null): ?float
    {
        $config ??= $this->config();

        $rows = CustomerCreditScore::where('customer_id', $customerId)->get();

        $counted = (int) $rows->sum('installments_counted');
        $score = null;

        if ($counted > 0) {
            $score = $this->normalize((float) $rows->sum('total_points') / $counted, $config);
        }

        // Query builder rather than the model, so recomputing a score does not
        // touch customers.updated_at -- that column tracks edits to the
        // customer's own record and the nightly job would otherwise stamp every
        // borrower in the book every night.
        Customer::withTrashed()->whereKey($customerId)->update([
            'credit_score'            => $score,
            'credit_score_updated_at' => $counted > 0 ? now() : null,
        ]);

        return $score;
    }

    /**
     * Map a per-installment average onto the displayed scale.
     *
     * The average naturally runs from -late_penalty_points (late every month)
     * to +on_time_points (punctual every month), which is a range that reads
     * badly in a UI and can go negative. This maps it linearly onto
     * 0..credit_score_normalize_max, so a perfect borrower scores the maximum
     * and one who was late every single month scores 0.
     *
     * Set credit_score_normalize_max to 0 to opt out and store the raw average
     * instead -- literally total points / number of installments.
     */
    public function normalize(?float $average, array $config): ?float
    {
        if ($average === null) {
            return null;
        }

        if ($config['normalize_max'] <= 0) {
            return round($average, 2);
        }

        $range = $config['on_time_points'] + $config['late_penalty_points'];

        // Both knobs set to zero: there is no scale to map onto and every
        // borrower would score the same. Report "no score" rather than a
        // meaningless number.
        if ($range <= 0) {
            return null;
        }

        $ratio = ($average + $config['late_penalty_points']) / $range;

        return round(min(1.0, max(0.0, $ratio)) * $config['normalize_max'], 2);
    }

    /**
     * A plain-language band for a normalised score, for badges and reports.
     *
     * Null for a customer with no history -- deliberately not 'poor', because
     * a first-time borrower has not earned a bad label.
     */
    public function band(?float $score, ?array $config = null): ?string
    {
        $config ??= $this->config();

        if ($score === null || $config['normalize_max'] <= 0) {
            return null;
        }

        $percentage = ($score / $config['normalize_max']) * 100;

        return match (true) {
            $percentage >= 90 => 'excellent',
            $percentage >= 75 => 'good',
            $percentage >= 50 => 'fair',
            $percentage >= 25 => 'poor',
            default           => 'very_poor',
        };
    }

    /**
     * The customers whose repayment this loan scores.
     *
     * A Group Loan gives every member their own installment rows and every
     * member their own score -- one member paying late must not mark the other
     * four. Individual and Joint loans score the loan's own customer, matching
     * InstallmentScheduleService::scheduleOwnersFor(), which is what decided
     * whose name went on the rows in the first place.
     *
     * @return array<int, int>
     */
    public function scoreOwnersFor(LoanApplication $loanApplication): array
    {
        if ($loanApplication->isGroupLoan()) {
            // Queried off LoanInstallment rather than through the relation on
            // purpose: installments() carries a default orderBy('installment_no'),
            // and MySQL under ONLY_FULL_GROUP_BY rejects a SELECT DISTINCT that
            // orders by a column it is not selecting.
            $ids = LoanInstallment::where('loan_application_id', $loanApplication->id)
                ->whereNotNull('customer_id')
                ->distinct()
                ->pluck('customer_id')
                ->all();

            return array_values(array_unique(array_map('intval', $ids)));
        }

        return $loanApplication->customer_id ? [(int) $loanApplication->customer_id] : [];
    }

    /**
     * The installments that belong to one score owner.
     *
     * A group loan filters by member; an individual loan takes the whole
     * schedule, including any early rows written before installments carried a
     * customer_id -- filtering those by customer_id would silently score the
     * borrower on a fraction of their own loan.
     */
    protected function installmentsFor(LoanApplication $loanApplication, int $customerId): Collection
    {
        if ($loanApplication->isGroupLoan()) {
            return $loanApplication->installments()
                ->where('customer_id', $customerId)
                ->orderBy('installment_no')
                ->get();
        }

        return $loanApplication->installments()->orderBy('installment_no')->get();
    }
}
