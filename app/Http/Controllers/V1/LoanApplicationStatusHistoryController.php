<?php

namespace App\Http\Controllers\V1;

use App\Enums\LoanApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\LoanApplicationStatusHistory;
use App\Models\User;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;


class LoanApplicationStatusHistoryController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Status History Index', only: ['index', 'show']),
        ];
    }

    /**
     * List status changes, newest first.
     *
     * Filters: loan_application_id, application_id, status (one or
     * comma-separated), changed_by, and a date_from/date_to range over
     * change_date.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = LoanApplicationStatusHistory::with([
                'application',
                'loanApplication:id,application_id,customer_id,branch_id,requested_amount,approved_amount,status',
                'loanApplication.customer',
                'changedBy:'.User::SUMMARY_COLUMNS,
            ]);

            if ($request->filled('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->filled('application_id')) {
                $query->where('application_id', $request->application_id);
            }

            if ($request->filled('status')) {
                $query->whereIn('loan_application_status', array_filter(array_map(
                    'trim',
                    explode(',', (string) $request->status)
                )));
            }

            if ($request->filled('changed_by')) {
                $query->where('changed_by', $request->changed_by);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('change_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('change_date', '<=', $request->date_to);
            }

            $branchId = $this->userBranchId();
            if ($branchId !== null) {
                $query->whereHas('loanApplication', fn ($q) => $q->where('branch_id', $branchId));
            }

            $history = $query
                ->orderBy('changed_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationStatusHistory', 'Loan application status history accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only([
                    'loan_application_id', 'application_id', 'status',
                    'changed_by', 'date_from', 'date_to',
                ]),
                'count'   => $history->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application status history retrieved successfully',
                'data'    => $history,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application status history',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    
    public function show(string $loanApplicationId)
    {
        try {
            $query = LoanApplicationStatusHistory::with([
                'changedBy:'.User::SUMMARY_COLUMNS,
            ])->where('loan_application_id', $loanApplicationId);

            $branchId = $this->userBranchId();
            if ($branchId !== null) {
                $query->whereHas('loanApplication', fn ($q) => $q->where('branch_id', $branchId));
            }

            $history = $query->orderBy('changed_at')->orderBy('id')->get();

            if ($history->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No status history found for this loan application',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application status history retrieved successfully',
                'data'    => [
                    'loan_application_id' => (int) $loanApplicationId,
                    'current_status'      => $history->last()->loan_application_status?->value,
                    'total_changes'       => $history->count(),
                    'history'             => $history,
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application status history',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * The statuses a file can be recorded in, for the filter dropdown.
     */
    public function statuses()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Loan application statuses retrieved successfully',
            'data'    => collect(LoanApplicationStatus::cases())
                ->map(fn ($case) => [
                    'value' => $case->value,
                    'label' => \Illuminate\Support\Str::title(str_replace('_', ' ', $case->value)),
                ])
                ->values(),
        ], 200);
    }
}
