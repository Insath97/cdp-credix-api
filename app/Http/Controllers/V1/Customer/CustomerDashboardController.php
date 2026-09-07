<?php

namespace App\Http\Controllers\V1\Customer;

use App\Enums\LoanApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Traits\ActivityLogTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDashboardController extends Controller
{
    use ActivityLogTrait, ResolvesAuthenticatedCustomerTrait;
    /**
     * Summary widgets for the customer dashboard landing screen.
     */
    public function overview(Request $request)
    {
        try {
            $customer = $this->myCustomer();
            $customerId = $customer->id;

            $activeStatuses = [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue];

            $activeLoans = LoanApplication::where('customer_id', $customerId)
                ->whereIn('status', $activeStatuses)
                ->get(['id', 'outstanding_balance']);

            $nextInstallment = LoanInstallment::whereHas('loanApplication', function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
                ->whereNotIn('status', ['paid', 'waived', 'revised'])
                ->orderBy('due_date')
                ->with('loanApplication.application')
                ->first();

            $overdueInstallments = LoanInstallment::whereHas('loanApplication', function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
                ->where('status', 'overdue')
                ->orderBy('due_date')
                ->get(['id', 'due_date', 'balance']);

            $overdueAmount = $overdueInstallments->sum('balance');
            $earliestOverdue = $overdueInstallments->first();
            $overdueDays = $earliestOverdue?->daysOverdue();

            $statusCounts = LoanApplication::where('customer_id', $customerId)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            // Keyed by the customer-facing status, so the three internal
            // pre-approval stages land in one 'processing' bucket instead of
            // counting the lender's review steps out on the borrower's own
            // dashboard. Every bucket is seeded to zero first so the shape of
            // the response does not change with the data.
            $loanSummary = collect(LoanApplicationStatus::cases())
                ->mapWithKeys(fn ($status) => [$status->customerFacingStatus() => 0])
                ->toArray();

            foreach ($statusCounts as $row) {
                $loanSummary[$row->status->customerFacingStatus()] += $row->total;
            }

            $loanSummary = ['total_loans' => array_sum($loanSummary)] + $loanSummary;

            $data = [
                'customer_name' => $customer->full_name,
                'customer_id' => $customer->customer_id,
                'customer_code' => $customer->customer_code,
                'active_loans_count' => $activeLoans->count(),
                'total_outstanding_amount' => $activeLoans->sum('outstanding_balance'),
                'loan_summary' => $loanSummary,
                'next_installment' => $nextInstallment ? [
                    // 'amount' reflects the live balance still owed (net of
                    // any overpayment carried forward from a prior
                    // installment) -- amount_due is kept alongside as the
                    // originally scheduled figure for anything that needs it.
                    'amount' => $nextInstallment->balance,
                    'amount_due' => $nextInstallment->amount_due,
                    'due_date' => $nextInstallment->due_date,
                    'loan_application_id' => $nextInstallment->loan_application_id,
                    'application_no' => $nextInstallment->loanApplication?->application?->application_no,
                ] : null,
                'overdue_amount' => $overdueAmount,
                'overdue_days' => $overdueDays,
            ];

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed dashboard overview', [
                'user_id' => auth('api')->id(),
                'customer_id' => $customerId,
            ]);

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
}
