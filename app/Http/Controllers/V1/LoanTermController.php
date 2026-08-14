<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLoanTermRequest;
use App\Http\Requests\UpdateLoanTermRequest;
use App\Models\LoanTerm;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanTermController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    /**
     * Define the middleware for this controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Term Index', ['only' => ['index', 'show']]),
            new Middleware('permission:Loan Term Create', ['only' => ['store']]),
            new Middleware('permission:Loan Term Update', ['only' => ['update']]),
            new Middleware('permission:Loan Term Delete', ['only' => ['destroy']]),
            new Middleware('permission:Loan Term Toggle Status', ['only' => ['toggleStatus']]),
        ];
    }

    /**
     * Display a listing of loan terms.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanTerm::with('loanTypes');

            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $loanTerms = $query->orderBy('title', 'asc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan terms retrieved successfully',
                'data' => $loanTerms,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan terms',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store a newly created loan term in storage.
     */
    public function store(CreateLoanTermRequest $request)
    {
        try {
            $data = $request->validated();
            $loanTypeIds = $data['loan_type_ids'] ?? [];
            unset($data['loan_type_ids']);

            $loanTerm = LoanTerm::create($data);
            $loanTerm->loanTypes()->sync($loanTypeIds);
            $loanTerm->load('loanTypes');

            $this->logActivity('CREATE', 'LoanTerm', "Created loan term: {$loanTerm->title}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan term created successfully',
                'data' => $loanTerm,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create loan term',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan term.
     */
    public function show(string $id)
    {
        try {
            $loanTerm = LoanTerm::with('loanTypes')->find($id);

            if (! $loanTerm) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan term not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Loan term retrieved successfully',
                'data' => $loanTerm,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve loan term',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified loan term in storage.
     */
    public function update(UpdateLoanTermRequest $request, string $id)
    {
        try {
            $loanTerm = LoanTerm::find($id);

            if (! $loanTerm) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan term not found',
                ], 404);
            }

            $data = $request->validated();

            if (array_key_exists('loan_type_ids', $data)) {
                $loanTerm->loanTypes()->sync($data['loan_type_ids']);
                unset($data['loan_type_ids']);
            }

            $loanTerm->update($data);
            $loanTerm->load('loanTypes');

            $this->logActivity('UPDATE', 'LoanTerm', "Updated loan term: {$loanTerm->title}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan term updated successfully',
                'data' => $loanTerm,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update loan term',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified loan term from storage.
     */
    public function destroy(string $id)
    {
        try {
            $loanTerm = LoanTerm::find($id);

            if (! $loanTerm) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan term not found',
                ], 404);
            }

            $loanTermTitle = $loanTerm->title;
            $loanTerm->loanTypes()->detach();
            $loanTerm->delete();

            $this->logActivity('DELETE', 'LoanTerm', "Deleted loan term: {$loanTermTitle}");

            return response()->json([
                'status' => 'success',
                'message' => 'Loan term deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete loan term',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get a list of all active loan terms (lightweight list).
     */
    public function getActiveList()
    {
        try {
            $loanTerms = LoanTerm::active()->orderBy('title', 'asc')->get(['id', 'title', 'code']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active loan terms retrieved successfully',
                'data' => $loanTerms,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active loan terms',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $loanTerm = LoanTerm::find($id);

            if (!$loanTerm) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan term not found'
                ], 404);
            }

            $loanTerm->is_active = !$loanTerm->is_active;
            $loanTerm->save();

            $this->logActivity('TOGGLE_STATUS', 'LoanTerm', "Toggled loan term status: {$loanTerm->title} (" . ($loanTerm->is_active ? 'Active' : 'Inactive') . ")");

            return response()->json([
                'status' => 'success',
                'message' => 'Loan term status updated successfully',
                'data' => [
                    'id' => $loanTerm->id,
                    'is_active' => $loanTerm->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle loan term status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
