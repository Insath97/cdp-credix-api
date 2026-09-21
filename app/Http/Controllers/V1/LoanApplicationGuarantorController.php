<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplicationGuarantor;
use App\Enums\LoanApplicationStatus;
use App\Services\LoanDocumentService;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationGuarantorRequest;
use App\Http\Requests\UpdateLoanApplicationGuarantorRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationGuarantorController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(
        protected LoanDocumentService $loanDocumentService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Guarantor Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Guarantor Create', only: ['store']),
            new Middleware('permission:Loan Application Guarantor Update', only: ['update']),
            new Middleware('permission:Loan Application Guarantor Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application guarantors.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationGuarantor::with(['loanApplication', 'guarantor']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('guarantor_id')) {
                $query->where('guarantor_id', $request->guarantor_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('guarantor_type')) {
                $query->where('guarantor_type', $request->guarantor_type);
            }

            $guarantors = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationGuarantor', 'Loan application guarantors index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'guarantor_id', 'status', 'guarantor_type']),
                'count'   => $guarantors->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application guarantors retrieved successfully',
                'data'    => $guarantors,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application guarantors',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created loan application guarantor.
     */
    public function store(CreateLoanApplicationGuarantorRequest $request)
    {
        try {
            $data = $request->validated();

            $record = LoanApplicationGuarantor::create($data);

            // The guarantor's papers now belong to this file. Linked here,
            // at the moment the relationship is made, rather than left for
            // the next review to pick up -- and linked to THIS application
            // only, which is the whole point of reading the pivot.
            if ($record->loanApplication) {
                $this->loanDocumentService->syncForApplication($record->loanApplication);
            }

            $this->logActivity('CREATE', 'LoanApplicationGuarantor', "Added guarantor ID {$record->guarantor_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application guarantor added successfully',
                'data'    => $record->load(['loanApplication', 'guarantor']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add loan application guarantor',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application guarantor.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationGuarantor::with(['loanApplication', 'guarantor'])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application guarantor not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application guarantor retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application guarantor',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified loan application guarantor.
     */
    public function update(UpdateLoanApplicationGuarantorRequest $request, string $id)
    {
        try {
            $record = LoanApplicationGuarantor::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application guarantor not found',
                ], 404);
            }

            $data = $request->validated();
            $record->update($data);

            $this->logActivity('UPDATE', 'LoanApplicationGuarantor', "Updated loan application guarantor ID {$record->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application guarantor updated successfully',
                'data'    => $record->fresh(['loanApplication', 'guarantor']),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update loan application guarantor',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified loan application guarantor from storage.
     */
    public function destroy(string $id)
    {
        try {
            $record = LoanApplicationGuarantor::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application guarantor not found',
                ], 404);
            }

            $loanApplication = $record->loanApplication;

            if ($loanApplication
                && $loanApplication->group_loan_id === null
                && $loanApplication->status !== LoanApplicationStatus::Submitted
                && $loanApplication->loanApplicationGuarantors()->count() <= 1) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This is the last remaining guarantor and cannot be removed once the loan application has passed verification.',
                ], 422);
            }

            // Their unreviewed papers leave the file with them. Anything an
            // officer already stamped stays as the audit record it is.
            if ($loanApplication) {
                $this->loanDocumentService->detachGuarantorDocuments($loanApplication, (int) $record->guarantor_id);
            }

            $record->delete();

            $this->logActivity('DELETE', 'LoanApplicationGuarantor', "Deleted loan application guarantor ID {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application guarantor deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete loan application guarantor',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
