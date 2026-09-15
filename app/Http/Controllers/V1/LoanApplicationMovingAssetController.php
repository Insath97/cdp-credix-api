<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoanApplicationMovingAsset;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateLoanApplicationMovingAssetRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LoanApplicationMovingAssetController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Loan Application Moving Asset Index',  only: ['index', 'show']),
            new Middleware('permission:Loan Application Moving Asset Create', only: ['store']),
            new Middleware('permission:Loan Application Moving Asset Delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of loan application moving assets.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = LoanApplicationMovingAsset::with(['loanApplication', 'movingAsset']);

            if ($request->has('loan_application_id')) {
                $query->where('loan_application_id', $request->loan_application_id);
            }

            if ($request->has('moving_assest_id')) {
                $query->where('moving_assest_id', $request->moving_assest_id);
            }

            $records = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'LoanApplicationMovingAsset', 'Loan application moving assets index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['loan_application_id', 'moving_assest_id']),
                'count'   => $records->count(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application moving assets retrieved successfully',
                'data'    => $records,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application moving assets',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Pledge an existing moving asset to a loan application.
     */
    public function store(CreateLoanApplicationMovingAssetRequest $request)
    {
        try {
            $data = $request->validated();

            $record = LoanApplicationMovingAsset::create($data);

            $this->logActivity('CREATE', 'LoanApplicationMovingAsset', "Pledged moving asset ID {$record->moving_assest_id} to loan application ID {$record->loan_application_id}", $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Moving asset pledged to loan application successfully',
                'data'    => $record->load(['loanApplication', 'movingAsset']),
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to pledge moving asset to loan application',
                'error'   => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified loan application moving asset.
     */
    public function show(string $id)
    {
        try {
            $record = LoanApplicationMovingAsset::with(['loanApplication', 'movingAsset'])->find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application moving asset not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Loan application moving asset retrieved successfully',
                'data'    => $record,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve loan application moving asset',
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
            $record = LoanApplicationMovingAsset::find($id);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Loan application moving asset not found',
                ], 404);
            }

            $record->delete();

            $this->logActivity('DELETE', 'LoanApplicationMovingAsset', "Removed moving asset pledge ID {$id}", [
                'record_id'  => $id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Moving asset pledge removed successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to remove moving asset pledge',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
