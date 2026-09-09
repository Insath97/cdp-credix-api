<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\CreditScoreEvent;
use App\Models\Customer;
use App\Models\CustomerCreditScore;
use App\Services\CreditScoreService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Read side of repayment credit scoring.
 *
 * Nothing here computes a score -- CreditScoreService owns that, and the only
 * write action is an explicit recompute an officer can trigger before making a
 * lending decision, so they are never reading a figure that went stale between
 * the last nightly run and now.
 */
class CreditScoreController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected CreditScoreService $creditScoreService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Credit Score Index',    only: ['index', 'show']),
            new Middleware('permission:Credit Score Recompute', only: ['recompute']),
        ];
    }

    /**
     * Customers ranked by repayment score.
     *
     * Only borrowers with at least one judged installment appear. A customer
     * who has never had a loan reach its first due date has nothing to score,
     * and listing them at the bottom next to genuine defaulters misrepresents
     * them -- CustomerController is where every customer lives. This is not
     * optional: the screen exists to compare repayment behaviour, and a row
     * with no behaviour in it is noise on every page of the list.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $config = $this->creditScoreService->config();

            $query = Customer::query()
                ->select(explode(',', Customer::SUMMARY_COLUMNS))
                ->whereNotNull('credit_score')
                ->search($request->get('search'));

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->get('branch_id'));
            }

            // Ascending puts the weakest borrowers first, which is the order a
            // recovery or risk officer actually wants; descending is there for
            // a "best customers" view.
            $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

            // credit_score_band rides along on every row: Customer appends it.
            // Plain orderBy is enough now that nulls are filtered out -- there
            // is no "sort the blanks last" case left to handle.
            $customers = $query
                ->orderBy('credit_score', $direction)
                ->paginate($perPage);

            return response()->json([
                'status'  => 'success',
                'message' => 'Credit scores retrieved successfully',
                'data'    => $customers,
                'meta'    => ['scale_max' => $config['normalize_max']],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve credit scores',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * One customer's full repayment record -- the screen an officer reads
     * before approving another loan.
     *
     * Returns the ledger as well as the totals, because "score 62" on its own
     * is not a decision an officer can defend; "late on 3 of 12 months, worst
     * 19 days" is.
     */
    public function show(Request $request, string $customerId)
    {
        try {
            $customer = Customer::select(explode(',', Customer::SUMMARY_COLUMNS))->find($customerId);

            if (!$customer) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customer not found',
                ], 404);
            }

            $config = $this->creditScoreService->config();

            $loanScores = CustomerCreditScore::with([
                    'loanApplication.application',
                    'loanApplication.loanProduct',
                ])
                ->where('customer_id', $customer->id)
                ->orderByDesc('computed_at')
                ->get()
                ->map(function (CustomerCreditScore $score) use ($config) {
                    return [
                        'loan_application_id'  => $score->loan_application_id,
                        'reference'            => $score->loanApplication?->reference(),
                        'loan_status'          => $score->loanApplication?->status,
                        'loan_product'         => $score->loanApplication?->loanProduct?->name,
                        'installments_counted' => $score->installments_counted,
                        'on_time_count'        => $score->on_time_count,
                        'late_count'           => $score->late_count,
                        'total_points'         => (float) $score->total_points,
                        'average_points'       => $score->average_points !== null ? (float) $score->average_points : null,
                        'score'                => $score->final_score !== null ? (float) $score->final_score : null,
                        'band'                 => $this->creditScoreService->band(
                            $score->final_score !== null ? (float) $score->final_score : null,
                            $config
                        ),
                        // False while the loan is still repaying: the score is
                        // a running tally, not the verdict.
                        'is_final'             => $score->finalized_at !== null,
                        'finalized_at'         => $score->finalized_at,
                        'computed_at'          => $score->computed_at,
                    ];
                });

            $events = CreditScoreEvent::with('loanInstallment:id,installment_no,due_date,amount_due,balance,status')
                ->where('customer_id', $customer->id)
                ->orderByDesc('occurred_on')
                ->orderByDesc('id')
                ->limit((int) $request->get('events_limit', 100))
                ->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'Credit score retrieved successfully',
                'data'    => [
                    'customer'      => $customer,
                    'score'         => $customer->credit_score !== null ? (float) $customer->credit_score : null,
                    'band'          => $this->creditScoreService->band(
                        $customer->credit_score !== null ? (float) $customer->credit_score : null,
                        $config
                    ),
                    // Null score plus this flag is how the UI tells "new
                    // borrower" apart from "scored zero".
                    'has_history'   => $customer->credit_score !== null,
                    'scale_max'     => $config['normalize_max'],
                    'updated_at'    => $customer->credit_score_updated_at,
                    'summary'       => [
                        'loans_scored'         => $loanScores->count(),
                        'loans_finalized'      => $loanScores->where('is_final', true)->count(),
                        'installments_counted' => (int) $loanScores->sum('installments_counted'),
                        'on_time_count'        => (int) $loanScores->sum('on_time_count'),
                        'late_count'           => (int) $loanScores->sum('late_count'),
                        'worst_days_late'      => (int) $events->max('days_late'),
                    ],
                    'loans'         => $loanScores,
                    'events'        => $events,
                    // Echoed back so a reviewer can see the rules the stored
                    // score was produced under, not just the number.
                    'scoring_rules' => $config,
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve credit score',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Rebuild one customer's scores on demand.
     *
     * The nightly job and the payment path keep these current, so this exists
     * for two cases: an officer about to decide on an application who wants to
     * be certain, and immediately after a settings change, where the stored
     * scores were produced under the old rules until something recomputes them.
     */
    public function recompute(string $customerId)
    {
        try {
            $customer = Customer::find($customerId);

            if (!$customer) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customer not found',
                ], 404);
            }

            if (!$this->creditScoreService->enabled()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Credit scoring is currently disabled in system settings.',
                ], 422);
            }

            $scores = $this->creditScoreService->recomputeForCustomer($customer->id);
            $customer->refresh();

            $this->logActivity('UPDATE', 'CustomerCreditScore', "Recomputed credit score for customer ID: {$customer->id}", [
                'customer_id'  => $customer->id,
                'loans_scored' => $scores->count(),
                'score'        => $customer->credit_score,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Credit score recomputed successfully',
                'data'    => [
                    'customer_id'  => $customer->id,
                    'score'        => $customer->credit_score !== null ? (float) $customer->credit_score : null,
                    'band'         => $this->creditScoreService->band(
                        $customer->credit_score !== null ? (float) $customer->credit_score : null
                    ),
                    'loans_scored' => $scores->count(),
                    'updated_at'   => $customer->credit_score_updated_at,
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to recompute credit score',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
