<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\LoanInstallment;
use App\Models\LoanApplication;
use App\Models\Customer;
use App\Services\CreditScoreService;
use App\Traits\ActivityLogTrait;
use App\Traits\ScopesToUserBranch;
use App\Http\Requests\CreateLoanInstallmentRequest;
use App\Http\Requests\UpdateLoanInstallmentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanInstallmentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, ScopesToUserBranch;

    public function __construct(
        protected CreditScoreService $creditScoreService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Installment Index',  only: ['index', 'show', 'list']),
            new Middleware('permission:Loan Installment Create', only: ['store']),
            new Middleware('permission:Loan Installment Update', only: ['update']),
            new Middleware('permission:Loan Installment Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan installments.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanInstallment::with([
                'customer:' . Customer::SUMMARY_COLUMNS,
                'loanApplication.customer:' . Customer::SUMMARY_COLUMNS,
                'loanApplication.loanProduct',
                'loanApplication.branch',
                'loanApplication.application',
                'loanApplication.groupLoan',
                'loanApplication.loanApplicationCustomers.customer:' . Customer::SUMMARY_COLUMNS,
            ]);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // A branch officer sees their own branch's installments only.
            $this->scopeToUserBranchVia($query, ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id']);

            $installments = $query->orderBy('due_date')->paginate($perPage);

            $this->logActivity('Index', 'LoanInstallment', 'Loan installments index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'status']),
                'count'   => $installments->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installments retrieved successfully',
                'data'    => $installments,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan installments',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a list of loan installments — the full schedule, paid months
     * included, since the installments page reads this.
     *
     * Pass `payable=true` to narrow it to months that can still take a
     * payment, which is what a payment picker wants. Either way a settled
     * month can never actually be paid: CreatePaymentRequest refuses it.
     */
    public function list(Request $request)
    {
        try {
            $query = LoanInstallment::with([
                'customer:' . Customer::SUMMARY_COLUMNS,
                'loanApplication.customer:' . Customer::SUMMARY_COLUMNS,
                'loanApplication.loanProduct',
                'loanApplication.branch',
                'loanApplication.application',
                'loanApplication.groupLoan',
                'loanApplication.loanApplicationCustomers.customer:' . Customer::SUMMARY_COLUMNS,
            ]);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Opt-in only: pass payable=true to get just the months that can
            // still take a payment. This endpoint returns the FULL schedule by
            // default — paid months included — because the installments page
            // reads it and needs to show them.
            if ($request->boolean('payable')) {
                $query->payable();
            }

            // Same confinement as index(): naming a loan_application_id from
            // another branch must not open its schedule.
            $this->scopeToUserBranchVia($query, ['loanApplication' => 'loan_application_id', 'customer' => 'customer_id']);

            if ($request->has('per_page')) {
                $installments = $query->orderBy('due_date')->paginate($request->get('per_page'));
            } else {
                $installments = $query->orderBy('due_date')->get();
            }

            $this->logActivity('Index', 'LoanInstallment', 'Loan installments list accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'status', 'payable']),
                'count'   => is_a($installments, \Illuminate\Pagination\AbstractPaginator::class) ? $installments->total() : $installments->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installments list retrieved successfully',
                'data'    => $installments,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan installments list',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created loan installment.
     */
    public function store(CreateLoanInstallmentRequest $request)
    {
        try {
            $data = $request->validated();

            // amount_due is never frontend-supplied — it always comes from the
            // parent loan application's backend-calculated monthly_installment.
            $loanApplication = LoanApplication::find($data['loan_application_id']);
            $data['amount_due'] = $loanApplication->monthly_installment;

            // Match InstallmentScheduleService: every installment carries a
            // customer. A Group Loan must name the member explicitly (the
            // request validates they are on the loan); anything else defaults
            // to the loan's own customer, so the row is never left unassigned.
            if (empty($data['customer_id'])) {
                $data['customer_id'] = $loanApplication->customer_id;
            }

            if (empty($data['balance'])) {
                $data['balance'] = $data['amount_due'] - ($data['amount_paid'] ?? 0);
            }

            $installment = LoanInstallment::create($data);

            $this->logActivity('CREATE', 'LoanInstallment', "Created loan installment ID: {$installment->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installment created successfully',
                'data'    => $installment->load(['loanApplication.customer:'.Customer::SUMMARY_COLUMNS, 'loanApplication.loanProduct', 'loanApplication.branch']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create loan installment',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan installment.
     */
    public function show(string $id)
    {
        try {
            $installment = LoanInstallment::with(['loanApplication.customer:'.Customer::SUMMARY_COLUMNS, 'loanApplication.loanProduct', 'loanApplication.branch'])->find($id);

            if (!$installment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan installment not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installment retrieved successfully',
                'data'    => $installment,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan installment',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified loan installment.
     */
    public function update(UpdateLoanInstallmentRequest $request, string $id)
    {
        try {
            $installment = LoanInstallment::find($id);

            if (!$installment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan installment not found',
                ], 404);
            }

            $data = $request->validated();

            DB::transaction(function () use ($installment, $data) {
                $penaltyBefore = $installment->penalty_amount;

                $installment->fill($data);
                $installment->recalculateBalance();
                $installment->save();

                $penaltyDelta = $installment->penalty_amount - $penaltyBefore;
                if ($penaltyDelta != 0) {
                    $loanApplication = $installment->loanApplication()->lockForUpdate()->first();
                    if ($loanApplication && $loanApplication->outstanding_balance !== null) {
                        $loanApplication->outstanding_balance = max(0, $loanApplication->outstanding_balance + $penaltyDelta);
                        $loanApplication->save();
                    }
                }
            });

            // A manual edit here is one of the few ways an installment's
            // scoring inputs change outside the payment path: waiving it takes
            // the month out of the score entirely, and editing status or
            // paid_at rewrites the on-time verdict. Post-commit and never
            // fatal, same contract as the payment path.
            $installment->loadMissing('loanApplication');

            if ($installment->loanApplication) {
                try {
                    $this->creditScoreService->recomputeForLoan(
                        $installment->loanApplication,
                        $installment->loanApplication->isGroupLoan() ? $installment->customer_id : null
                    );
                } catch (\Throwable $th) {
                    Log::warning('Credit score recompute failed after installment edit', [
                        'loan_installment_id' => $installment->id,
                        'error'               => $th->getMessage(),
                    ]);
                }
            }

            $this->logActivity('UPDATE', 'LoanInstallment', "Updated loan installment ID: {$installment->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installment updated successfully',
                'data'    => $installment->fresh(['loanApplication.customer:'.Customer::SUMMARY_COLUMNS, 'loanApplication.loanProduct', 'loanApplication.branch']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update loan installment',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified loan installment from storage.
     */
    public function destroy(string $id)
    {
        try {
            $installment = LoanInstallment::find($id);

            if (!$installment) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan installment not found',
                ], 404);
            }

            $installment->delete();

            $this->logActivity('DELETE', 'LoanInstallment', "Deleted loan installment ID: {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installment deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete loan installment',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
