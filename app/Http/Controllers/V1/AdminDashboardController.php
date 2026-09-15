<?php

namespace App\Http\Controllers\V1;

use App\Enums\LoanApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminDashboard;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Admin Dashboard Index', only: ['overview', 'recentTransactions', 'recentLoanApplications', 'targetIndex']),
        ];
    }

    /**
     * The branch every figure on this screen is counted over.
     *
     * A branch officer is confined to their own posting, whatever branch_id
     * they send: the dashboard was the one screen still totalling the whole
     * company for them, so their customer list showed Colombo while the
     * portfolio figure above it covered every branch.
     *
     * Head office (anyone with no branch posting) keeps the request filter, so
     * branch_id stays a way to look at one branch at a time.
     */
    protected function resolveBranchId(Request $request): ?int
    {
        $ownBranchId = $this->userBranchId();

        if ($ownBranchId !== null) {
            return $ownBranchId;
        }

        return $request->filled('branch_id') ? (int) $request->branch_id : null;
    }

    /**
     * Resolve the [startDate, endDate] Carbon pair from the request, defaulting
     * to the current month to date.
     */
    protected function resolveDateRange(Request $request): array
    {
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth();

        return [$startDate, $endDate];
    }

    /**
     * Dashboard landing screen: summary cards, target progress, the 12-month
     * balance chart, top loan products, and short previews of recent
     * transactions/applications.
     */
    public function overview(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request);
            $branchId = $this->resolveBranchId($request);
            $periodDays = $startDate->diffInDays($endDate) + 1;
            $priorStart = $startDate->copy()->subDays($periodDays);
            $priorEnd = $startDate->copy()->subDay()->endOfDay();

            $baseLoanQuery = fn () => LoanApplication::when($branchId, fn ($q) => $q->where('branch_id', $branchId));

            // Total Disbursed (period) + trend vs the immediately preceding period of equal length.
            $totalDisbursed = (clone $baseLoanQuery())->whereBetween('disbursed_at', [$startDate, $endDate])->sum('approved_amount');
            $priorDisbursed = (clone $baseLoanQuery())->whereBetween('disbursed_at', [$priorStart, $priorEnd])->sum('approved_amount');

            // Total Applications (period) + trend.
            $totalApplications = (clone $baseLoanQuery())->whereBetween('applied_at', [$startDate, $endDate])->count();
            $priorApplications = (clone $baseLoanQuery())->whereBetween('applied_at', [$priorStart, $priorEnd])->count();

            // Outstanding Portfolio is a point-in-time balance, not period-bound. The "30 days ago"
            // comparison is reconstructed from how outstanding_balance actually changes (only via
            // disbursement and payments), since no historical balance snapshots exist:
            //   balance_at_period_start = current_balance - disbursed_in_period + paid_in_period
            $currentOutstanding = (clone $baseLoanQuery())->whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])->sum('outstanding_balance');
            $paidInPeriod = Payment::whereHas('loanApplication', fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q)
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount');
            $outstandingAtPeriodStart = $currentOutstanding - $totalDisbursed + $paidInPeriod;

            $data = [
                'period' => ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()],
                'summary' => [
                    'total_disbursed' => (float) $totalDisbursed,
                    'total_disbursed_trend_pct' => $this->trendPercent($totalDisbursed, $priorDisbursed),
                    'outstanding_portfolio' => (float) $currentOutstanding,
                    'outstanding_portfolio_trend_pct' => $this->trendPercent($currentOutstanding, $outstandingAtPeriodStart),
                    'total_applications' => $totalApplications,
                    'total_applications_trend_pct' => $this->trendPercent($totalApplications, $priorApplications),
                ],
                'targets' => $this->computeTargets($startDate, $endDate, $branchId, $totalDisbursed, $paidInPeriod, $totalApplications),
                'balance_chart' => $this->buildBalanceChart($endDate, $branchId),
                'top_loan_products' => $this->topLoanProducts($startDate, $endDate, $branchId),
                'recent_transactions' => $this->shapeTransactions(Payment::with(['loanApplication.application', 'loanApplication.customer:'.Customer::SUMMARY_COLUMNS])
                    ->when($branchId, fn ($q) => $q->whereHas('loanApplication', fn ($q2) => $q2->where('branch_id', $branchId)))
                    ->orderByDesc('paid_at')->limit(5)->get()),
                'recent_loan_applications' => $this->shapeApplications((clone $baseLoanQuery())
                    ->with(['application', 'loanProduct', 'customer:'.Customer::SUMMARY_COLUMNS])
                    ->orderByDesc('applied_at')->limit(5)->get()),
            ];

            $this->logActivity('Index', 'AdminDashboard', 'Admin viewed dashboard overview', ['user_id' => Auth::id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard overview retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard overview',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Full paginated "Recent Transactions" list (the overview only shows a 5-row preview).
     */
    public function recentTransactions(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            // Same confinement as overview(): the list must not reach past the
            // officer's own branch just because it is a different endpoint.
            $branchId = $this->resolveBranchId($request);

            $payments = Payment::with(['loanApplication.application', 'loanApplication.customer:'.Customer::SUMMARY_COLUMNS])
                ->when($branchId, fn ($q) => $q->whereHas('loanApplication', fn ($q2) => $q2->where('branch_id', $branchId)))
                ->orderByDesc('paid_at')
                ->paginate($perPage);

            $payments->getCollection()->transform(fn ($payment) => $this->shapeTransaction($payment));

            return response()->json([
                'status' => 'success',
                'message' => 'Recent transactions retrieved successfully',
                'data' => $payments,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve recent transactions',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Full paginated "Recent Loan Applications" list (the overview only shows a 5-row preview).
     */
    public function recentLoanApplications(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            // Same confinement as overview(): the list must not reach past the
            // officer's own branch just because it is a different endpoint.
            $branchId = $this->resolveBranchId($request);

            $applications = LoanApplication::with(['application', 'loanProduct', 'customer:'.Customer::SUMMARY_COLUMNS])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->orderByDesc('applied_at')
                ->paginate($perPage);

            $applications->getCollection()->transform(fn ($loanApplication) => $this->shapeApplication($loanApplication));

            return response()->json([
                'status' => 'success',
                'message' => 'Recent loan applications retrieved successfully',
                'data' => $applications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve recent loan applications',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List configured dashboard targets.
     */
    public function targetIndex(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            // Targets are set per branch; an officer sees their own branch's.
            $branchId = $this->resolveBranchId($request);

            $targets = AdminDashboard::with(['branch', 'createdBy:'.User::SUMMARY_COLUMNS])
                ->when($request->has('metric'), fn ($q) => $q->where('metric', $request->metric))
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->orderByDesc('period_start')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard targets retrieved successfully',
                'data' => $targets,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard targets',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Percentage change from $previous to $current, null when $previous is 0
     * (division by zero has no meaningful percentage change).
     */
    protected function trendPercent($current, $previous): ?float
    {
        if ((float) $previous == 0.0) {
            return null;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }

    /**
     * Compute the 3 target progress bars for the requested period.
     *
     * Disbursement/Recovery targets are currency amounts: displayed % = actual amount ÷ target amount.
     * Approval Rate target is itself a percentage (0-100): the bar shows the RAW approval rate for
     * the period (approved ÷ total applications), with the configured target exposed alongside it
     * for the frontend to optionally show "vs goal" rather than compounding a rate-of-a-rate.
     */
    protected function computeTargets(Carbon $startDate, Carbon $endDate, ?int $branchId, $totalDisbursed, $paidInPeriod, int $totalApplications): array
    {
        $referenceDate = $endDate->toDateString();

        $disbursementTarget = AdminDashboard::forPeriod('disbursement', $referenceDate, $branchId)->first();
        $recoveryTarget = AdminDashboard::forPeriod('recovery', $referenceDate, $branchId)->first();
        $approvalTarget = AdminDashboard::forPeriod('approval_rate', $referenceDate, $branchId)->first();

        $recoveryActual = Payment::whereHas('loanApplication', function ($q) use ($branchId) {
            $q->whereHas('recoveryCases');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereBetween('paid_at', [$startDate, $endDate])->sum('amount');

        $approvedInPeriod = LoanApplication::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('applied_at', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->count();
        $approvalRateActual = $totalApplications > 0 ? round(($approvedInPeriod / $totalApplications) * 100, 1) : 0.0;

        return [
            'disbursement' => [
                'actual' => (float) $totalDisbursed,
                'target' => $disbursementTarget?->target_value,
                'percentage' => $disbursementTarget && $disbursementTarget->target_value > 0
                    ? min(100, round(((float) $totalDisbursed / (float) $disbursementTarget->target_value) * 100, 1))
                    : null,
            ],
            'recovery' => [
                'actual' => (float) $recoveryActual,
                'target' => $recoveryTarget?->target_value,
                'percentage' => $recoveryTarget && $recoveryTarget->target_value > 0
                    ? min(100, round(((float) $recoveryActual / (float) $recoveryTarget->target_value) * 100, 1))
                    : null,
            ],
            'approval_rate' => [
                'actual' => $approvalRateActual,
                'target' => $approvalTarget?->target_value,
                'percentage' => $approvalRateActual,
            ],
        ];
    }

    /**
     * Monthly Disbursements vs Repayments for the 12 months ending at $endDate.
     */
    protected function buildBalanceChart(Carbon $endDate, ?int $branchId): array
    {
        $rangeStart = $endDate->copy()->subMonths(11)->startOfMonth();

        $disbursements = LoanApplication::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('disbursed_at', [$rangeStart, $endDate])
            ->select(DB::raw("DATE_FORMAT(disbursed_at, '%Y-%m') as ym"), DB::raw('sum(approved_amount) as total'))
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $repayments = Payment::whereHas('loanApplication', fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q)
            ->whereBetween('paid_at', [$rangeStart, $endDate])
            ->select(DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as ym"), DB::raw('sum(amount) as total'))
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $rangeStart->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $months[] = [
                'month' => $month->format('M'),
                'year' => $month->year,
                'disbursements' => (float) ($disbursements->get($key) ?? 0),
                'repayments' => (float) ($repayments->get($key) ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Top loan products by application count within the period.
     */
    protected function topLoanProducts(Carbon $startDate, Carbon $endDate, ?int $branchId): array
    {
        return LoanProduct::withCount(['loanApplications' => function ($query) use ($startDate, $endDate, $branchId) {
                $query->whereBetween('applied_at', [$startDate, $endDate]);
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            }])
            ->orderByDesc('loan_applications_count')
            ->limit(5)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'interest_rate' => $product->interest_rate,
                'applications_count' => $product->loan_applications_count,
                'is_active' => $product->is_active,
            ])
            ->toArray();
    }

    /**
     * Shape a collection of payments into the "Recent Transactions" response.
     */
    protected function shapeTransactions($payments): array
    {
        return $payments->map(fn ($payment) => $this->shapeTransaction($payment))->toArray();
    }

    protected function shapeTransaction(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'loan_account' => $payment->loanApplication?->application?->application_no,
            'receipt_no' => $payment->receipt_no,
            'paid_at' => $payment->paid_at,
            'amount' => $payment->amount,
            'borrower' => $payment->loanApplication?->customer?->full_name,
            'status' => 'completed',
        ];
    }

    /**
     * Shape a collection of loan applications into the "Recent Loan Applications" response.
     */
    protected function shapeApplications($applications): array
    {
        return $applications->map(fn ($loanApplication) => $this->shapeApplication($loanApplication))->toArray();
    }

    protected function shapeApplication(LoanApplication $loanApplication): array
    {
        return [
            'id' => $loanApplication->id,
            'loan_product' => $loanApplication->loanProduct?->name,
            'application_no' => $loanApplication->application?->application_no,
            'applied_date' => $loanApplication->applied_at,
            'requested_amount' => $loanApplication->requested_amount,
            'borrower' => $loanApplication->customer?->full_name,
            'status' => $loanApplication->status->value,
        ];
    }
}
