<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplicationBankDetail;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationBankDetailRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationBankDetailController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Bank Detail Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Bank Detail Create', only: ['store']),
            new Middleware('permission:Loan Application Bank Detail Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application bank details.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationBankDetail::with(['loanApplication', 'bankDetail']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('customer_bank_detail_id')) {
                $query->where('customer_bank_detail_id', $request->customer_bank_detail_id);
            }

            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationBankDetail', 'Loan application bank details index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'customer_bank_detail_id']),
                'count'   => $records->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application bank details retrieved successfully',
                'data'    => $records,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application bank details',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Link an existing bank detail to a loan application.
     */
    public function store(CreateLoanApplicationBankDetailRequest $request)
    {
        try {
            $data = $request->validated();

            $record = LoanApplicationBankDetail::create($data);

            $this->logActivity('CREATE', 'LoanApplicationBankDetail', "Linked bank detail ID {$record->customer_bank_detail_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Bank detail linked to loan application successfully',
                'data'    => $record->load(['loanApplication', 'bankDetail']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to link bank detail to loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application bank detail.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationBankDetail::with(['loanApplication', 'bankDetail'])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application bank detail not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application bank detail retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application bank detail',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the link (unlink the bank detail from the loan application).
     */
    public function destroy(string $id)
    {
        try {
            $record = LoanApplicationBankDetail::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application bank detail not found',
                ], 404);
            }

            $record->delete();

            $this->logActivity('DELETE', 'LoanApplicationBankDetail', "Removed bank detail link ID {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Bank detail link removed successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove bank detail link',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
