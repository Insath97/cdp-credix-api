<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use App\Models\Customer;
use App\Enums\LoanApplicationStatus;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationCustomerRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationCustomerController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Customer Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Customer Create', only: ['store']),
            new Middleware('permission:Loan Application Customer Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application customers (Joint Loan co-borrowers).
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationCustomer::with(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS]);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationCustomer', 'Loan application customers index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id']),
                'count'   => $records->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application customers retrieved successfully',
                'data'    => $records,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application customers',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach a co-borrower to a loan application, turning it into a Joint Loan.
     */
    public function store(CreateLoanApplicationCustomerRequest $request)
    {
        try {
            $data = $request->validated();

            $loanApplication = LoanApplication::find($data['loan_application_id']);

            // First co-borrower ever added: bring the loan's own primary customer
            // into the pivot too, so it always holds the complete borrower set.
            if ($loanApplication && $loanApplication->loanApplicationCustomers()->count() === 0) {
                LoanApplicationCustomer::create([
                    'loan_application_id' => $loanApplication->id,
                    'customer_id'         => $loanApplication->customer_id,
                ]);
            }

            $record = LoanApplicationCustomer::create($data);

            $this->logActivity('CREATE', 'LoanApplicationCustomer', "Added customer ID {$record->customer_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Customer added to loan application successfully',
                'data'    => $record->load(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS]),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add customer to loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application customer.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationCustomer::with(['loanApplication', 'customer:'.Customer::SUMMARY_COLUMNS])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application customer not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application customer retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application customer',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a co-borrower from the loan application.
     */
    public function destroy(string $id)
    {
        try {
            $record = LoanApplicationCustomer::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application customer not found',
                ], 404);
            }

            $loanApplication = $record->loanApplication;

            if ($loanApplication && $loanApplication->status !== LoanApplicationStatus::Submitted) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customers can only be removed while the loan application is in Submitted status.',
                ], 422);
            }

            $record->delete();

            $this->logActivity('DELETE', 'LoanApplicationCustomer', "Removed customer ID {$record->customer_id} from loan application ID {$record->loan_application_id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Customer removed from loan application successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove customer from loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
