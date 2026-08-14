<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplicationLiability;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationLiabilityRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationLiabilityController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Liability Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Liability Create', only: ['store']),
            new Middleware('permission:Loan Application Liability Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application liabilities.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationLiability::with(['loanApplication', 'liability']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('liability_id')) {
                $query->where('liability_id', $request->liability_id);
            }

            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationLiability', 'Loan application liabilities index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'liability_id']),
                'count'   => $records->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application liabilities retrieved successfully',
                'data'    => $records,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application liabilities',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Link an existing liability to a loan application.
     */
    public function store(CreateLoanApplicationLiabilityRequest $request)
    {
        try {
            $data = $request->validated();

            $record = LoanApplicationLiability::create($data);

            $this->logActivity('CREATE', 'LoanApplicationLiability', "Linked liability ID {$record->liability_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Liability linked to loan application successfully',
                'data'    => $record->load(['loanApplication', 'liability']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to link liability to loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application liability.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationLiability::with(['loanApplication', 'liability'])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application liability not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application liability retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application liability',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the link (unlink the liability from the loan application).
     */
    public function destroy(string $id)
    {
        try {
            $record = LoanApplicationLiability::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application liability not found',
                ], 404);
            }

            $record->delete();

            $this->logActivity('DELETE', 'LoanApplicationLiability', "Removed liability link ID {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Liability link removed successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove liability link',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
