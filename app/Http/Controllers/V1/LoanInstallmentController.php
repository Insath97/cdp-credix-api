<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanInstallment;
use App\Models\LoanApplication;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanInstallmentRequest;
use App\Http\Requests\UpdateLoanInstallmentRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanInstallmentController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

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
            $query = LoanInstallment::with(['loanApplication.customer', 'loanApplication.loanProduct', 'loanApplication.branch']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

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
     * Get a list of loan installments.
     */
    public function list(Request $request)
    {
        try {
            $query = LoanInstallment::with(['loanApplication.customer', 'loanApplication.loanProduct', 'loanApplication.branch']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('per_page')) {
                $installments = $query->orderBy('due_date')->paginate($request->get('per_page'));
            } else {
                $installments = $query->orderBy('due_date')->get();
            }

            $this->logActivity('Index', 'LoanInstallment', 'Loan installments list accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'status']),
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

            if (empty($data['balance'])) {
                $data['balance'] = $data['amount_due'] - ($data['amount_paid'] ?? 0);
            }

            $installment = LoanInstallment::create($data);

            $this->logActivity('CREATE', 'LoanInstallment', "Created loan installment ID: {$installment->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installment created successfully',
                'data'    => $installment->load(['loanApplication.customer', 'loanApplication.loanProduct', 'loanApplication.branch']),
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
            $installment = LoanInstallment::with(['loanApplication.customer', 'loanApplication.loanProduct', 'loanApplication.branch'])->find($id);

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
            $installment->update($data);

            $this->logActivity('UPDATE', 'LoanInstallment', "Updated loan installment ID: {$installment->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan installment updated successfully',
                'data'    => $installment->fresh(['loanApplication.customer', 'loanApplication.loanProduct', 'loanApplication.branch']),
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
