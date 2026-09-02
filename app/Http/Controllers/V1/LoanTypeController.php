<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLoanTypeRequest;
use App\Http\Requests\UpdateLoanTypeRequest;
use App\Models\LoanType;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanTypeController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    /**
     * Define the middleware for this controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Type Index', ['only' => ['index', 'show']]),
            new Middleware('permission:Loan Type Create', ['only' => ['store']]),
            new Middleware('permission:Loan Type Update', ['only' => ['update']]),
            new Middleware('permission:Loan Type Delete', ['only' => ['destroy']]),
            new Middleware('permission:Loan Type Toggle Status', ['only' => ['toggleStatus']]),
        ];
    }

    /**
     * Display a listing of loan types.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanType::query();

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $loanTypes = $query->orderBy('title', 'asc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan types retrieved successfully',
                'data' => $loanTypes,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan types',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store a newly created loan type in storage.
     */
    public function store(CreateLoanTypeRequest $request)
    {
        try {
            $data = $request->validated();
            $loanType = LoanType::create($data);

            $this->logActivity('CREATE', 'LoanType', "Created loan type: {$loanType->title}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan type created successfully',
                'data' => $loanType,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create loan type',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan type.
     */
    public function show(string $id)
    {
        try {
            $loanType = LoanType::find($id);

            if (! $loanType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan type not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Loan type retrieved successfully',
                'data' => $loanType,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan type',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified loan type in storage.
     */
    public function update(UpdateLoanTypeRequest $request, string $id)
    {
        try {
            $loanType = LoanType::find($id);

            if (! $loanType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan type not found',
                ], 404);
            }

            $data = $request->validated();
            $loanType->update($data);

            $this->logActivity('UPDATE', 'LoanType', "Updated loan type: {$loanType->title}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan type updated successfully',
                'data' => $loanType,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update loan type',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified loan type from storage.
     */
    public function destroy(string $id)
    {
        try {
            $loanType = LoanType::find($id);

            if (! $loanType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan type not found',
                ], 404);
            }

            $loanTypeTitle = $loanType->title;
            $loanType->loanTerms()->detach();
            $loanType->delete();

            $this->logActivity('DELETE', 'LoanType', "Deleted loan type: {$loanTypeTitle}");

            return response()->json([
                'status' => 'success',
                'message' => 'Loan type deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete loan type',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get a list of all active loan types (lightweight list, for multi-select dropdowns).
     */
    public function getActiveList()
    {
        try {
            $loanTypes = LoanType::active()->orderBy('title', 'asc')->get(['id', 'title', 'code', 'loan_term_id']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active loan types retrieved successfully',
                'data' => $loanTypes,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active loan types',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $loanType = LoanType::with('loanTerm')->find($id);

            if (! $loanType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan type not found',
                ], 404);
            }

            $loanType->is_active = !$loanType->is_active;
            $loanType->save();

            $this->logActivity('TOGGLE_STATUS', 'LoanType', "Toggled loan type status: {$loanType->title} (" . ($loanType->is_active ? 'Active' : 'Inactive') . ")");

            return response()->json([
                'status' => 'success',
                'message' => 'Loan type status updated successfully',
                'data' => [
                    'id' => $loanType->id,
                    'is_active' => $loanType->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle loan type status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
