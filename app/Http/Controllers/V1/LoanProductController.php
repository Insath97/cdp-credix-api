<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanProduct;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanProductRequest;
use App\Http\Requests\UpdateLoanProductRequest;
use App\Models\LoanType;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanProductController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Product Index',  only: ['index', 'show', 'getActiveList']),
            new Middleware('permission:Loan Product Create', only: ['store']),
            new Middleware('permission:Loan Product Update', only: ['update']),
            new Middleware('permission:Loan Product Toggle Status', only: ['toggleStatus', 'activate', 'deactivate']),
            new Middleware('permission:Loan Product Delete', only: ['destroy']),
        ];
    }

    /**
     * Resolve the loan term id for a loan product from its loan type.
     * The term is always derived from the type (loan_types.loan_term_id).
     */
    private function resolveTermForType(int $loanTypeId): ?int
    {
        return LoanType::find($loanTypeId)?->loan_term_id;
    }

    /**
     * Display a listing of loan products.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanProduct::query();

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('interest_type')) {
                $query->where('interest_type', $request->interest_type);
            }

            if ($request->has('processing_fee_type')) {
                $query->where('processing_fee_type', $request->processing_fee_type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $loanProducts = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanProduct', 'Loan products index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'interest_type', 'processing_fee_type', 'is_active']),
                'count'   => $loanProducts->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan products retrieved successfully',
                'data'    => $loanProducts,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan products',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created loan product.
     */
    public function store(CreateLoanProductRequest $request)
    {
        try {
            $data = $request->validated();

            // The loan term for a product is always derived from the selected
            // loan type's term (loan_types.loan_term_id) — never taken from an
            // independently-supplied value. This keeps term/type consistent.
            $data['loan_term_id'] = $this->resolveTermForType($data['loan_type_id']);

            $loanProduct = LoanProduct::create($data);

            $this->logActivity('CREATE', 'LoanProduct', "Created loan product: {$loanProduct->name}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan product created successfully',
                'data'    => $loanProduct,
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create loan product',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan product.
     */
    public function show(string $id)
    {
        try {
            $loanProduct = LoanProduct::find($id);

            if (!$loanProduct) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan product not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan product retrieved successfully',
                'data'    => $loanProduct,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan product',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified loan product.
     */
    public function update(UpdateLoanProductRequest $request, string $id)
    {
        try {
            $loanProduct = LoanProduct::find($id);

            if (!$loanProduct) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan product not found',
                ], 404);
            }

            $data = $request->validated();

            // Keep the product's term in sync with the loan type's term.
            if (!empty($data['loan_type_id'])) {
                $data['loan_term_id'] = $this->resolveTermForType($data['loan_type_id']);
            }

            $loanProduct->update($data);

            $this->logActivity('UPDATE', 'LoanProduct', "Updated loan product: {$loanProduct->name}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan product updated successfully',
                'data'    => $loanProduct->fresh(),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update loan product',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Toggle the is_active status of a loan product.
     */
    public function toggleStatus(string $id)
    {
        try {
            $loanProduct = LoanProduct::query()->find($id);

            if (!$loanProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan product not found'
                ], 404);
            }

            $loanProduct->is_active = !$loanProduct->is_active;
            $loanProduct->save();

            Log::info('Loan product status toggled', [
                'user_id' => Auth::id(),
                'loan_product_id' => $loanProduct->id,
                'new_status' => $loanProduct->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan product status updated successfully',
                'data' => [
                    'id' => $loanProduct->id,
                    'is_active' => $loanProduct->is_active
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle loan product status',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a loan product.
     */
    public function activate(string $id)
    {
        try {
            $loanProduct = LoanProduct::query()->find($id);

            if (! $loanProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan product not found',
                ], 404);
            }

            if ($loanProduct->is_active) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Loan product is already active',
                    'data' => $loanProduct
                ]);
            }

            $loanProduct->update(['is_active' => true]);

            Log::info('Loan product activated', [
                'user_id' => Auth::id(),
                'loan_product_id' => $loanProduct->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan product activated successfully',
                'data' => $loanProduct
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate loan product',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate a loan product.
     */
    public function deactivate(string $id)
    {
        try {
            $loanProduct = LoanProduct::query()->find($id);

            if (! $loanProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Loan product not found',
                ], 404);
            }

            if (! $loanProduct->is_active) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Loan product is already inactive',
                    'data' => $loanProduct
                ]);
            }

            $loanProduct->update(['is_active' => false]);

            Log::info('Loan product deactivated', [
                'user_id' => Auth::id(),
                'loan_product_id' => $loanProduct->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Loan product deactivated successfully',
                'data' => $loanProduct
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate loan product',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft-delete the specified loan product.
     */
    public function destroy(string $id)
    {
        try {
            $loanProduct = LoanProduct::find($id);

            if (!$loanProduct) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan product not found',
                ], 404);
            }

            $loanProduct->delete();

            $this->logActivity('DELETE', 'LoanProduct', "Deleted loan product: {$loanProduct->name}", [
                'loan_product_id' => $id,
                'deleted_by'      => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan product deleted successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete loan product',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active loan products as a simple list (for dropdowns).
     */
    public function getActiveList()
    {
        try {
            $loanProducts = LoanProduct::active()
                ->select('id', 'name', 'code', 'interest_rate', 'interest_type', 'min_amount', 'max_amount')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'Active loan products retrieved successfully',
                'data'    => $loanProducts,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan products list',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
