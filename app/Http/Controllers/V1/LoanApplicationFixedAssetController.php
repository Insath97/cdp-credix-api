<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplicationFixedAsset;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationFixedAssetRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationFixedAssetController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Fixed Asset Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Fixed Asset Create', only: ['store']),
            new Middleware('permission:Loan Application Fixed Asset Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application fixed assets.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationFixedAsset::with(['loanApplication', 'fixedAsset']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('fixed_assest_id')) {
                $query->where('fixed_assest_id', $request->fixed_assest_id);
            }

            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationFixedAsset', 'Loan application fixed assets index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'fixed_assest_id']),
                'count'   => $records->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application fixed assets retrieved successfully',
                'data'    => $records,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application fixed assets',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Pledge an existing fixed asset to a loan application.
     */
    public function store(CreateLoanApplicationFixedAssetRequest $request)
    {
        try {
            $data = $request->validated();

            $record = LoanApplicationFixedAsset::create($data);

            $this->logActivity('CREATE', 'LoanApplicationFixedAsset', "Pledged fixed asset ID {$record->fixed_assest_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Fixed asset pledged to loan application successfully',
                'data'    => $record->load(['loanApplication', 'fixedAsset']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to pledge fixed asset to loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application fixed asset.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationFixedAsset::with(['loanApplication', 'fixedAsset'])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application fixed asset not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application fixed asset retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application fixed asset',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the pledge (unlink the asset from the loan application).
     */
    public function destroy(string $id)
    {
        try {
            $record = LoanApplicationFixedAsset::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application fixed asset not found',
                ], 404);
            }

            $record->delete();

            $this->logActivity('DELETE', 'LoanApplicationFixedAsset', "Removed fixed asset pledge ID {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Fixed asset pledge removed successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove fixed asset pledge',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
