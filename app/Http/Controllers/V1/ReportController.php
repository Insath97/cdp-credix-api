<?php

namespace App\Http\Controllers\V1;

use App\Enums\LoanApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\RecoveryCase;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Report Index', only: ['branchWise', 'customerWise', 'loanPortfolio', 'recovery', 'recoveryShow']),
        ];
    }

    /**
     * Resolve the [startDate, endDate] Carbon pair from the request, defaulting
     * to the current month to date. Mirrors AdminDashboardController::resolveDateRange().
     */
    protected function resolveDateRange(Request $request): array
    {
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth();

        return [$startDate, $endDate];
    }

    /**
     * Rows per page, clamped.
     *
     * Every report shapes each row with its own aggregate queries, so an
     * unbounded per_page is a handful of characters that turns one request
     * into tens of thousands of queries.
     */
    protected function reportPerPage(Request $request): int
    {
        return max(1, min((int) $request->get('per_page', 15) ?: 15, 100));
    }

    /**
     * The branch every figure in a report is counted over.
     *
     * A branch officer is confined to their own posting whatever branch_id
     * they send. Reports were reading the request alone, so an officer whose
     * customer list stops at Colombo could open Reports and read the whole
     * company's disbursement, collections and recovery -- and these are the
     * numbers people print and act on.
     *
     * Head office (no branch posting) keeps the request filter, so branch_id
     * stays a way to look at one branch at a time.
     */
    protected function reportBranchId(Request $request): ?int
    {
        $ownBranchId = $this->userBranchId();

        if ($ownBranchId !== null) {
            return $ownBranchId;
        }

        return $request->filled('branch_id') ? (int) $request->branch_id : null;
    }

    /**
     * Human-readable summary of the filters applied, shown on PDF exports.
     */
    protected function filterSummary(Request $request, Carbon $startDate, Carbon $endDate): string
    {
        $parts = ["{$startDate->toDateString()} to {$endDate->toDateString()}"];

        // The branch actually applied, not the one asked for: an officer is
        // confined to their own, and the export footer has to say so.
        if ($branchId = $this->reportBranchId($request)) {
            $branchName = Branch::find($branchId)?->name ?? "#{$branchId}";
            $parts[] = "Branch: {$branchName}";
        }
        if ($request->filled('search')) {
            $parts[] = "Search: \"{$request->search}\"";
        }
        if ($request->filled('status')) {
            $parts[] = "Status: {$request->status}";
        }

        return implode('  |  ', $parts);
    }

    // -------------------------------------------------------------------
    // Branch-wise report
    // -------------------------------------------------------------------

    public function branchWise(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request);
            $perPage = $this->reportPerPage($request);

            $branches = $this->branchWiseBaseQuery($request)->paginate($perPage);
            $branches->getCollection()->transform(fn ($branch) => $this->shapeBranchRow($branch, $startDate, $endDate));

            $this->logActivity('Index', 'Report', 'Branch-wise report viewed', ['user_id' => Auth::id()]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Branch-wise report retrieved successfully',
                'data'    => $branches,
                'summary' => $this->branchWiseSummary($request, $startDate, $endDate),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve branch-wise report',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function branchWiseExport(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request);
            $format = $request->get('format', 'pdf');

            $rows = $this->branchWiseBaseQuery($request)->get()
                ->map(fn ($branch) => $this->shapeBranchRow($branch, $startDate, $endDate));

            $this->logActivity('Export', 'Report', "Branch-wise report exported ({$format})", ['user_id' => Auth::id()]);

            if ($format === 'xlsx') {
                return Excel::download(new BranchWiseReportExport($rows), 'branch-wise-report.xlsx');
            }

            return Pdf::loadView('reports.branch-wise', [
                'title'   => 'Branch-wise Report',
                'filters' => $this->filterSummary($request, $startDate, $endDate),
                'rows'    => $rows,
            ])->download('branch-wise-report.pdf');

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export branch-wise report',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    protected function branchWiseBaseQuery(Request $request)
    {
        return Branch::query()
            // One row per branch, so the confinement is on which branches are
            // listed at all: an officer's branch-wise report is their branch.
            ->when($this->reportBranchId($request), fn ($q, $bid) => $q->where('id', $bid))
            ->when($request->filled('search'), fn ($q) => $q->search($request->search))
            ->orderBy('name');
    }

    /**
     * Totals across every branch the filters admit, not just the page.
     *
     * The screen shows these beside the branch count, and a figure that
     * silently covered fifteen branches out of forty would be read as the
     * company's disbursement.
     */
    protected function branchWiseSummary(Request $request, Carbon $startDate, Carbon $endDate): array
    {
        $branchIds = $this->branchWiseBaseQuery($request)->reorder()->select('id');

        $applications = LoanApplication::whereIn('branch_id', $branchIds);

        return [
            'branches_count'        => (clone $branchIds)->count(),
            'total_disbursed'       => (float) (clone $applications)->whereBetween('disbursed_at', [$startDate, $endDate])->sum('approved_amount'),
            'outstanding_portfolio' => (float) (clone $applications)->whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])->sum('outstanding_balance'),
            'total_collected'       => (float) Payment::whereHas('loanApplication', fn ($q) => $q->whereIn('branch_id', $branchIds))
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount'),
        ];
    }

    protected function shapeBranchRow(Branch $branch, Carbon $startDate, Carbon $endDate): array
    {
        $applications = LoanApplication::where('branch_id', $branch->id);

        return [
            'branch_id'             => $branch->id,
            'branch_code'           => $branch->code,
            'branch_name'           => $branch->name,
            'applications_count'    => (clone $applications)->whereBetween('applied_at', [$startDate, $endDate])->count(),
            'total_disbursed'       => (float) (clone $applications)->whereBetween('disbursed_at', [$startDate, $endDate])->sum('approved_amount'),
            'outstanding_portfolio' => (float) (clone $applications)->whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])->sum('outstanding_balance'),
            'total_collected'       => (float) Payment::whereHas('loanApplication', fn ($q) => $q->where('branch_id', $branch->id))
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount'),
        ];
    }

    // -------------------------------------------------------------------
    // Customer-wise report
    // -------------------------------------------------------------------

    public function customerWise(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request);
            $perPage = $this->reportPerPage($request);

            $customers = $this->customerWiseBaseQuery($request)->paginate($perPage);
            $customers->getCollection()->transform(fn ($customer) => $this->shapeCustomerRow($customer, $startDate, $endDate));

            $this->logActivity('Index', 'Report', 'Customer-wise report viewed', ['user_id' => Auth::id()]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Customer-wise report retrieved successfully',
                'data'    => $customers,
                'summary' => $this->customerWiseSummary($request, $startDate, $endDate),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve customer-wise report',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function customerWiseExport(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request);
            $format = $request->get('format', 'pdf');

            $rows = $this->customerWiseBaseQuery($request)->get()
                ->map(fn ($customer) => $this->shapeCustomerRow($customer, $startDate, $endDate));

            $this->logActivity('Export', 'Report', "Customer-wise report exported ({$format})", ['user_id' => Auth::id()]);

            if ($format === 'xlsx') {
                return Excel::download(new CustomerWiseReportExport($rows), 'customer-wise-report.xlsx');
            }

            return Pdf::loadView('reports.customer-wise', [
                'title'   => 'Customer-wise Report',
                'filters' => $this->filterSummary($request, $startDate, $endDate),
                'rows'    => $rows,
            ])->download('customer-wise-report.pdf');

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export customer-wise report',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    protected function customerWiseBaseQuery(Request $request)
    {
        return Customer::query()
            ->when($request->filled('search'), fn ($q) => $q->search($request->search))
            ->when($this->reportBranchId($request), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->orderBy('full_name');
    }

    /**
     * Totals across every customer the filters admit -- see
     * branchWiseSummary() for why these cannot come off the page.
     */
    protected function customerWiseSummary(Request $request, Carbon $startDate, Carbon $endDate): array
    {
        $customerIds = $this->customerWiseBaseQuery($request)->reorder()->select('id');

        $applications = LoanApplication::whereIn('customer_id', $customerIds);

        return [
            'customers_count'     => (clone $customerIds)->count(),
            'total_disbursed'     => (float) (clone $applications)->whereBetween('disbursed_at', [$startDate, $endDate])->sum('approved_amount'),
            'outstanding_balance' => (float) (clone $applications)->whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])->sum('outstanding_balance'),
            'total_paid'          => (float) Payment::whereHas('loanApplication', fn ($q) => $q->whereIn('customer_id', $customerIds))
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount'),
            'overdue_amount'      => (float) LoanInstallment::where('status', 'overdue')
                ->whereHas('loanApplication', fn ($q) => $q->whereIn('customer_id', $customerIds))
                ->sum('balance'),
        ];
    }

    protected function shapeCustomerRow(Customer $customer, Carbon $startDate, Carbon $endDate): array
    {
        $applications = LoanApplication::where('customer_id', $customer->id);

        return [
            'customer_id'        => $customer->id,
            'customer_code'      => $customer->customer_code,
            'full_name'          => $customer->full_name,
            'total_loans'        => (clone $applications)->count(),
            'total_disbursed'    => (float) (clone $applications)->whereBetween('disbursed_at', [$startDate, $endDate])->sum('approved_amount'),
            'outstanding_balance' => (float) (clone $applications)->whereIn('status', [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue])->sum('outstanding_balance'),
            'total_paid'         => (float) Payment::whereHas('loanApplication', fn ($q) => $q->where('customer_id', $customer->id))
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('amount'),
            'overdue_amount'     => (float) LoanInstallment::where('status', 'overdue')
                ->whereHas('loanApplication', fn ($q) => $q->where('customer_id', $customer->id))
                ->sum('balance'),
        ];
    }

    // -------------------------------------------------------------------
    // Loan portfolio report
    // -------------------------------------------------------------------

    public function loanPortfolio(Request $request)
    {
        try {
            $perPage = $this->reportPerPage($request);

            $applications = $this->loanPortfolioBaseQuery($request)->paginate($perPage);
            $applications->getCollection()->transform(fn ($loanApplication) => $this->shapeLoanPortfolioRow($loanApplication));

            $this->logActivity('Index', 'Report', 'Loan portfolio report viewed', ['user_id' => Auth::id()]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan portfolio report retrieved successfully',
                'data'    => $applications,
                'summary' => $this->loanPortfolioSummary($request),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan portfolio report',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    protected function loanPortfolioBaseQuery(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        return LoanApplication::with(['application', 'customer:'.Customer::SUMMARY_COLUMNS, 'loanProduct', 'branch'])
            ->when($this->reportBranchId($request), fn ($q, $bid) => $q->where('branch_id', $bid))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('application', fn ($q3) => $q3->where('application_no', 'like', "%$search%"))
                        ->orWhereHas('customer', fn ($q3) => $q3->where('full_name', 'like', "%$search%")->orWhere('customer_code', 'like', "%$search%"));
                });
            })
            ->whereBetween('applied_at', [$startDate, $endDate])
            ->orderByDesc('applied_at');
    }

    /**
     * Totals across every loan the filters admit -- see branchWiseSummary()
     * for why these cannot come off the page.
     */
    protected function loanPortfolioSummary(Request $request): array
    {
        $base = $this->loanPortfolioBaseQuery($request)->reorder()->withoutEagerLoads();

        return [
            'loans_count'         => (clone $base)->count(),
            'requested_amount'    => (float) (clone $base)->sum('requested_amount'),
            'approved_amount'     => (float) (clone $base)->sum('approved_amount'),
            'outstanding_balance' => (float) (clone $base)->sum('outstanding_balance'),
        ];
    }

    protected function shapeLoanPortfolioRow(LoanApplication $loanApplication): array
    {
        return [
            'id'                => $loanApplication->id,
            'application_no'    => $loanApplication->application?->application_no,
            'customer_name'     => $loanApplication->customer?->full_name,
            'branch_name'       => $loanApplication->branch?->name,
            'loan_product'      => $loanApplication->loanProduct?->name,
            'requested_amount'  => (float) $loanApplication->requested_amount,
            'approved_amount'   => $loanApplication->approved_amount !== null ? (float) $loanApplication->approved_amount : null,
            'status'            => $loanApplication->status->value,
            'applied_at'        => $loanApplication->applied_at,
            'disbursed_at'      => $loanApplication->disbursed_at,
            'outstanding_balance' => $loanApplication->outstanding_balance !== null ? (float) $loanApplication->outstanding_balance : null,
        ];
    }

    // -------------------------------------------------------------------
    // Recovery / Collection report
    // -------------------------------------------------------------------

    public function recovery(Request $request)
    {
        try {
            [$startDate, $endDate] = $this->resolveDateRange($request);
            $perPage = $this->reportPerPage($request);

            $cases = $this->recoveryBaseQuery($request)->paginate($perPage);
            $cases->getCollection()->transform(fn ($case) => $this->shapeRecoveryRow($case));

            $this->logActivity('Index', 'Report', 'Recovery report viewed', ['user_id' => Auth::id()]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery report retrieved successfully',
                'data'    => $cases,
                'summary' => $this->recoverySummary($request, $startDate, $endDate),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery report',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Full detail for a single recovery case row, including its activity
     * timeline -- backs the report's "expand row" drill-down.
     */
    public function recoveryShow(string $id)
    {
        try {
            $case = RecoveryCase::with([
                'loanApplication.customer:'.Customer::SUMMARY_COLUMNS,
                'loanApplication.application',
                'loanApplication.branch',
                'assignedAgent:'.User::SUMMARY_COLUMNS,
                'externalAgent',
                'activities.performedBy:'.User::SUMMARY_COLUMNS,
            ])->find($id);

            if (!$case) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Recovery case not found',
                ], 404);
            }

            $data = $this->shapeRecoveryRow($case);
            $data['activities'] = $case->activities->map(fn ($activity) => [
                'id'              => $activity->id,
                'activity_type'   => $activity->activity_type,
                'notes'           => $activity->notes,
                'promised_amount' => $activity->promised_amount !== null ? (float) $activity->promised_amount : null,
                'promised_date'   => $activity->promised_date,
                'performed_by'    => $activity->performedBy?->name,
                'performed_at'    => $activity->performed_at,
            ]);

            $this->logActivity('Show', 'Report', "Recovery report detail viewed for case ID: {$case->id}", ['user_id' => Auth::id()]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Recovery case detail retrieved successfully',
                'data'    => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve recovery case detail',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    protected function recoveryBaseQuery(Request $request)
    {
        return RecoveryCase::with(['loanApplication.customer:'.Customer::SUMMARY_COLUMNS, 'loanApplication.application', 'loanApplication.branch', 'assignedAgent:'.User::SUMMARY_COLUMNS, 'externalAgent'])
            ->when($this->reportBranchId($request), fn ($q, $bid) => $q->whereHas('loanApplication', fn ($q2) => $q2->where('branch_id', $bid)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->stage))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->where('case_no', 'like', "%$search%")
                        ->orWhereHas('loanApplication.customer', fn ($q3) => $q3->where('full_name', 'like', "%$search%")->orWhere('customer_code', 'like', "%$search%"));
                });
            })
            ->whereBetween('opened_at', [$request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth(), $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay()])
            ->orderByDesc('opened_at');
    }

    protected function shapeRecoveryRow(RecoveryCase $case): array
    {
        return [
            'id'                 => $case->id,
            'case_no'            => $case->case_no,
            'customer_name'      => $case->loanApplication?->customer?->full_name,
            'application_no'     => $case->loanApplication?->application?->application_no,
            'branch_name'        => $case->loanApplication?->branch?->name,
            'status'             => $case->status,
            'stage'              => $case->stage,
            'overdue_amount'     => $case->overdue_amount !== null ? (float) $case->overdue_amount : null,
            'assigned_agent_name' => $case->assignedAgent?->name,
            'external_agent_name' => $case->externalAgent?->name,
            'opened_at'          => $case->opened_at,
            'closed_at'          => $case->closed_at,
        ];
    }

    /**
     * Aggregate totals for the recovery report (shown once, not per row):
     * total overdue balance right now, and how much has been collected
     * against overdue loans within the resolved date range.
     */
    protected function recoverySummary(Request $request, Carbon $startDate, Carbon $endDate): array
    {
        $branchId = $this->reportBranchId($request);

        $totalOverdue = LoanInstallment::where('status', 'overdue')
            ->when($branchId, fn ($q) => $q->whereHas('loanApplication', fn ($q2) => $q2->where('branch_id', $branchId)))
            ->sum('balance');

        $totalCollected = Payment::whereHas('loanApplication', function ($q) use ($branchId) {
            $q->whereHas('recoveryCases');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereBetween('paid_at', [$startDate, $endDate])->sum('amount');

        return [
            'total_overdue_balance'      => (float) $totalOverdue,
            'total_collected_in_period'  => (float) $totalCollected,
        ];
    }
}
