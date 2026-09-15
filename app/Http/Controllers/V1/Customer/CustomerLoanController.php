<?php

namespace App\Http\Controllers\V1\Customer;

use App\Enums\LoanApplicationStatus;
use App\Enums\LoanRevisionStatus;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRevision;
use App\Models\Payment;
use App\Traits\ActivityLogTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerLoanController extends Controller
{
    use ActivityLogTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * List the customer's loan applications (any status), the pre-disbursement pipeline view.
     */
    public function applications(Request $request)
    {
        try {
            $customerId = $this->myCustomerId();
            $perPage = $request->get('per_page', 15);

            $query = LoanApplication::forCustomer($customerId)
                ->with(['application', 'loanProduct']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $applications = $query->orderByDesc('applied_at')->paginate($perPage);

            $applications->getCollection()->transform(fn ($loanApplication) => $this->shapeApplication($loanApplication));

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed loan applications', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan applications retrieved successfully',
                'data' => $applications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan applications',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show a single loan application belonging to the customer.
     */
    public function applicationShow(string $id)
    {
        try {
            $customerId = $this->myCustomerId();

            $loanApplication = LoanApplication::where('id', $id)
                ->forCustomer($customerId)
                ->with(['application', 'loanProduct'])
                ->first();

            if (!$loanApplication) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $data = $this->shapeApplication($loanApplication);
            $data['approval_remarks'] = $loanApplication->approval_remarks;

            return response()->json([
                'status' => 'success',
                'message' => 'Loan application retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan application',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List the customer's disbursed loans (Disbursed/Active/Overdue/Closed).
     */
    public function index(Request $request)
    {
        try {
            $customerId = $this->myCustomerId();
            $perPage = $request->get('per_page', 15);

            $loans = LoanApplication::forCustomer($customerId)
                ->whereIn('status', [
                    LoanApplicationStatus::Disbursed,
                    LoanApplicationStatus::Active,
                    LoanApplicationStatus::Overdue,
                    LoanApplicationStatus::Closed,
                ])
                ->with(['application', 'loanProduct.loanType', 'loanProduct.loanTerm'])
                ->orderByDesc('disbursed_at')
                ->paginate($perPage);

            $ids = $loans->pluck('id');
            [$paidSums, $aggregates] = $this->computeLoanAggregates($ids);

            $loans->getCollection()->transform(function ($loanApplication) use ($paidSums, $aggregates) {
                return $this->shapeLoan(
                    $loanApplication,
                    $paidSums->get($loanApplication->id, 0),
                    $aggregates->get($loanApplication->id)
                );
            });

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed loans list', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Loans retrieved successfully',
                'data' => $loans,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loans',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show a single loan belonging to the customer.
     */
    public function show(string $id)
    {
        try {
            $customerId = $this->myCustomerId();

            $loanApplication = LoanApplication::where('id', $id)
                ->forCustomer($customerId)
                ->with(['application', 'branch', 'loanProduct.loanType', 'loanProduct.loanTerm'])
                ->first();

            if (!$loanApplication) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan not found',
                ], 404);
            }

            [$paidSums, $aggregates] = $this->computeLoanAggregates(collect([$loanApplication->id]));

            $data = $this->shapeLoan($loanApplication, $paidSums->get($loanApplication->id, 0), $aggregates->get($loanApplication->id));
            $data['requested_amount'] = $loanApplication->requested_amount;
            $data['interest_rate'] = $loanApplication->interest_rate;
            $data['interest_type'] = $loanApplication->interest_type;
            $data['processing_fee'] = $loanApplication->processing_fee;
            $data['branch'] = $loanApplication->branch?->name;
            $data['loan_product'] = [
                'id' => $loanApplication->loanProduct?->id,
                'name' => $loanApplication->loanProduct?->name,
                'is_islamic' => $loanApplication->loanProduct?->is_islamic,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Loan retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Full repayment schedule for one of the customer's loans.
     */
    public function installments(string $id)
    {
        try {
            $customerId = $this->myCustomerId();

            $ownsLoan = LoanApplication::where('id', $id)->forCustomer($customerId)->exists();

            if (!$ownsLoan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan not found',
                ], 404);
            }

            $installments = LoanInstallment::where('loan_application_id', $id)
                ->where('status', '!=', 'revised')
                ->orderBy('installment_no')
                ->get();

            $nextDue = $installments->first(fn ($installment) => !in_array($installment->status, ['paid', 'waived']));

            $shaped = $installments->map(function ($installment) use ($nextDue) {
                return $this->shapeInstallment($installment, $nextDue?->id === $installment->id);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Installments retrieved successfully',
                'data' => [
                    'installments' => $shaped,
                    'next_installment' => $nextDue ? $this->shapeInstallment($nextDue, true) : null,
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve installments',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Loan revision (restructuring) history for one of the customer's loans.
     */
    public function revisions(string $id)
    {
        try {
            $customerId = $this->myCustomerId();

            $ownsLoan = LoanApplication::where('id', $id)->forCustomer($customerId)->exists();

            if (!$ownsLoan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan not found',
                ], 404);
            }

            $revisions = LoanRevision::where('loan_application_id', $id)
                ->with(['installments' => fn ($q) => $q->orderBy('installment_no')])
                ->orderByDesc('revision_no')
                ->get();

            $data = $revisions->map(function ($revision) {
                return [
                    'id' => $revision->id,
                    'revision_no' => $revision->revision_no,
                    'revision_type' => $revision->revision_type,
                    'status' => $revision->status->value,
                    'previous_installment_amount' => $revision->previous_installment_amount,
                    'revised_installment_amount' => $revision->revised_installment_amount,
                    'previous_term' => $revision->previous_term,
                    'revised_term' => $revision->revised_term,
                    'previous_outstanding_amount' => $revision->previous_outstanding_amount,
                    'revised_outstanding_amount' => $revision->revised_outstanding_amount,
                    'reason' => $revision->reason,
                    'requested_date' => $revision->created_at,
                    'effective_date' => $revision->effective_date,
                    'new_installments' => $revision->status === LoanRevisionStatus::Approved
                        ? $revision->installments->map(fn ($installment) => $this->shapeInstallment($installment, false))
                        : [],
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Loan revisions retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan revisions',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * A combined statement bundle for one of the customer's loans.
     */
    public function statement(string $id)
    {
        try {
            $customer = $this->myCustomer();

            $loanApplication = LoanApplication::where('id', $id)
                ->forCustomer($customer->id)
                ->with(['application', 'loanProduct.loanType', 'loanProduct.loanTerm'])
                ->first();

            if (!$loanApplication) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan not found',
                ], 404);
            }

            $payments = Payment::where('loan_application_id', $id)->orderBy('paid_at')->get();
            $installments = LoanInstallment::where('loan_application_id', $id)
                ->where('status', '!=', 'revised')
                ->orderBy('installment_no')
                ->get();
            $revisions = LoanRevision::where('loan_application_id', $id)->orderByDesc('revision_no')->get();

            [$paidSums, $aggregates] = $this->computeLoanAggregates(collect([$loanApplication->id]));
            $aggregate = $aggregates->get($loanApplication->id);

            $data = [
                'customer' => [
                    'full_name' => $customer->full_name,
                    'customer_id' => $customer->customer_id,
                    'customer_code' => $customer->customer_code,
                    'id_number' => $customer->id_number,
                    'address_line_1' => $customer->address_line_1,
                    'phone_primary' => $customer->phone_primary,
                    'email' => $customer->email,
                ],
                'loan' => [
                    'loan_number' => $loanApplication->application?->application_no,
                    'loan_product' => $loanApplication->loanProduct?->name,
                    'loan_type' => $loanApplication->loanProduct?->loanType?->title,
                    'loan_term' => $loanApplication->loanProduct?->loanTerm?->title,
                    'status' => $loanApplication->status->customerFacingStatus(),
                    'disbursed_at' => $loanApplication->disbursed_at,
                    'maturity_date' => $aggregate->maturity_date ?? null,
                    'requested_amount' => $loanApplication->requested_amount,
                    'approved_amount' => $loanApplication->approved_amount,
                    'total_payable_amount' => $aggregate->total_payable ?? 0,
                    'profit_amount' => ($aggregate->total_payable ?? 0) - $loanApplication->approved_amount,
                    'interest_rate' => $loanApplication->interest_rate,
                    'interest_type' => $loanApplication->interest_type,
                ],
                'outstanding_balance' => $loanApplication->outstanding_balance,
                'payment_history' => $payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'receipt_no' => $payment->receipt_no,
                    'paid_at' => $payment->paid_at,
                    'installment_no' => $payment->loanInstallment?->installment_no,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_status' => 'completed',
                ]),
                'installment_schedule' => $installments->map(fn ($installment) => $this->shapeInstallment($installment, false)),
                'revision_history' => $revisions->map(fn ($revision) => [
                    'revision_no' => $revision->revision_no,
                    'revision_type' => $revision->revision_type,
                    'status' => $revision->status->value,
                    'effective_date' => $revision->effective_date,
                ]),
                'generated_at' => now(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Loan statement retrieved successfully',
                'data' => $data,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan statement',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Shape a loan application into the "Loan Applications" section response.
     */
    protected function shapeApplication(LoanApplication $loanApplication): array
    {
        return [
            'id' => $loanApplication->id,
            'application_no' => $loanApplication->application?->application_no,
            'loan_product' => $loanApplication->loanProduct?->name,
            'requested_amount' => $loanApplication->requested_amount,
            'applied_date' => $loanApplication->applied_at,
            // Masked, not raw: the review / verification / approval stages are
            // internal maker-checker steps and collapse to 'processing' here.
            'status' => $loanApplication->status->customerFacingStatus(),
            'current_stage' => $loanApplication->status->customerFacingLabel(),
            'rejection_reason' => $loanApplication->rejection_reason,
        ];
    }

    /**
     * Shape a loan application into the "My Loans" section response.
     */
    protected function shapeLoan(LoanApplication $loanApplication, float $paidAmount, $aggregate): array
    {
        $totalPayable = $aggregate->total_payable ?? 0;

        return [
            'id' => $loanApplication->id,
            'loan_number' => $loanApplication->application?->application_no,
            'loan_product' => $loanApplication->loanProduct?->name,
            'loan_type' => $loanApplication->loanProduct?->loanType?->title,
            'loan_term' => $loanApplication->loanProduct?->loanTerm?->title ?? $loanApplication->term_months,
            'approved_amount' => $loanApplication->approved_amount,
            'total_payable_amount' => $totalPayable,
            'paid_amount' => $paidAmount,
            'outstanding_balance' => $loanApplication->outstanding_balance,
            'profit_amount' => $totalPayable - $loanApplication->approved_amount,
            'monthly_installment' => $loanApplication->monthly_installment,
            'remaining_installments' => $aggregate->remaining ?? 0,
            'start_date' => $loanApplication->disbursed_at,
            'maturity_date' => $aggregate->maturity_date ?? null,
            'status' => $loanApplication->status->customerFacingStatus(),
            'is_islamic' => $loanApplication->loanProduct?->is_islamic,
        ];
    }

    /**
     * Shape a single installment row.
     */
    protected function shapeInstallment(LoanInstallment $installment, bool $isNextDue): array
    {
        return [
            'id' => $installment->id,
            'installment_no' => $installment->installment_no,
            'due_date' => $installment->due_date,
            'amount_due' => $installment->amount_due,
            'amount_paid' => $installment->amount_paid,
            'balance' => $installment->balance,
            'status' => $installment->status,
            'is_next_due' => $isNextDue,
        ];
    }

    /**
     * Batch-compute paid amounts and installment aggregates for a set of loan application ids,
     * avoiding an N+1 query per row on paginated loan lists.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    protected function computeLoanAggregates($loanApplicationIds): array
    {
        $paidSums = Payment::whereIn('loan_application_id', $loanApplicationIds)
            ->select('loan_application_id', DB::raw('sum(amount) as paid'))
            ->groupBy('loan_application_id')
            ->pluck('paid', 'loan_application_id');

        $aggregates = LoanInstallment::whereIn('loan_application_id', $loanApplicationIds)
            ->where('status', '!=', 'revised')
            ->select(
                'loan_application_id',
                DB::raw('sum(amount_due) as total_payable'),
                DB::raw("sum(case when status not in ('paid','waived') then 1 else 0 end) as remaining"),
                DB::raw('max(due_date) as maturity_date')
            )
            ->groupBy('loan_application_id')
            ->get()
            ->keyBy('loan_application_id');

        return [$paidSums, $aggregates];
    }
}
