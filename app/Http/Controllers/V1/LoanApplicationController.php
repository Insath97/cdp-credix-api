<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\LoanApplication;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationRequest;
use App\Http\Requests\UpdateLoanApplicationRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Create', only: ['store']),
            new Middleware('permission:Loan Application Update', only: ['update']),
            new Middleware('permission:Loan Application Toggle Status', only: ['toggleStatus', 'activate', 'deactivate']),
            new Middleware('permission:Loan Application Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan applications.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplication::with([
                'application',
                'customer',
                'loanProduct',
                'branch',
                'appliedBy',
                'reviewedBy',
                'approvedBy'
            ]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('interest_type')) {
                $query->where('interest_type', $request->interest_type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $loanApplications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplication', 'Loan applications index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'interest_type', 'is_active']),
                'count'   => $loanApplications->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan applications retrieved successfully',
                'data'    => $loanApplications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan applications',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created loan application.
     */
    public function store(CreateLoanApplicationRequest $request)
    {
        try {
            $data = $request->validated();
            
            if (empty($data['applied_by'])) {
                $data['applied_by'] = Auth::id();
            }
            if (empty($data['applied_at'])) {
                $data['applied_at'] = now();
            }

            $loanApplication = LoanApplication::create($data);

            $this->logActivity('CREATE', 'LoanApplication', "Created loan application ID: {$loanApplication->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application created successfully',
                'data'    => $loanApplication->load([
                    'application',
                    'customer',
                    'loanProduct',
                    'branch',
                    'appliedBy'
                ]),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application.
     */
    public function show(string $id)
    {
        try {
            $loanApplication = LoanApplication::with([
                'application',
                'customer',
                'loanProduct',
                'branch',
                'appliedBy',
                'reviewedBy',
                'approvedBy'
            ])->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application retrieved successfully',
                'data'    => $loanApplication,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified loan application.
     */
    public function update(UpdateLoanApplicationRequest $request, string $id)
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $data = $request->validated();
            
            // Check for review & approval transitions to log or track timestamps
            if (isset($data['reviewed_by']) && empty($loanApplication->reviewed_at)) {
                $data['reviewed_at'] = now();
            }
            if (isset($data['approved_by']) && empty($loanApplication->approved_at)) {
                $data['approved_at'] = now();
            }

            $loanApplication->update($data);

            $this->logActivity('UPDATE', 'LoanApplication', "Updated loan application ID: {$loanApplication->id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application updated successfully',
                'data'    => $loanApplication->fresh([
                    'application',
                    'customer',
                    'loanProduct',
                    'branch',
                    'appliedBy',
                    'reviewedBy',
                    'approvedBy'
                ]),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Toggle the is_active status of a loan application.
     */
    public function toggleStatus(string $id)
    {
        try {
            $loanApplication = LoanApplication::query()->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found'
                ], 404);
            }

            $loanApplication->is_active = !$loanApplication->is_active;
            $loanApplication->save();

            Log::info('Loan application status toggled', [
                'user_id'             => Auth::id(),
                'loan_application_id' => $loanApplication->id,
                'new_status'          => $loanApplication->is_active
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application status updated successfully',
                'data'    => [
                    'id'        => $loanApplication->id,
                    'is_active' => $loanApplication->is_active
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to toggle loan application status',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a loan application.
     */
    public function activate(string $id)
    {
        try {
            $loanApplication = LoanApplication::query()->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if ($loanApplication->is_active) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Loan application is already active',
                    'data'    => $loanApplication
                ]);
            }

            $loanApplication->update(['is_active' => true]);

            Log::info('Loan application activated', [
                'user_id'             => Auth::id(),
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application activated successfully',
                'data'    => $loanApplication
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to activate loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate a loan application.
     */
    public function deactivate(string $id)
    {
        try {
            $loanApplication = LoanApplication::query()->find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            if (!$loanApplication->is_active) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Loan application is already inactive',
                    'data'    => $loanApplication
                ]);
            }

            $loanApplication->update(['is_active' => false]);

            Log::info('Loan application deactivated', [
                'user_id'             => Auth::id(),
                'loan_application_id' => $loanApplication->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application deactivated successfully',
                'data'    => $loanApplication
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to deactivate loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft-delete the specified loan application.
     */
    public function destroy(string $id)
    {
        try {
            $loanApplication = LoanApplication::find($id);

            if (!$loanApplication) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application not found',
                ], 404);
            }

            $loanApplication->delete();

            $this->logActivity('DELETE', 'LoanApplication', "Deleted loan application ID: {$loanApplication->id}", [
                'loan_application_id' => $id,
                'deleted_by'          => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete loan application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
