<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\FixedAssests;
use App\Models\Customer;
use App\Enums\LoanApplicationStatus;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\CreateFixedAssetsRequest;
use App\Http\Requests\UpdateFixedAssetsRequest;


use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FixedAssestsController extends Controller implements HasMiddleware
{
     use ActivityLogTrait;

     public static function middleware(): array
     {
         return [
             new Middleware('permission:Fixed Asset Index', only: ['index', 'show']),
             new Middleware('permission:Fixed Asset Create', only: ['store']),
             new Middleware('permission:Fixed Asset Update', only: ['update']),
             new Middleware('permission:Fixed Asset Delete', only: ['destroy']),
         ];
     }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = FixedAssests::with(['customer:' . Customer::SUMMARY_COLUMNS]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('used_for_loan')) {
                $terminalStatuses = [
                    LoanApplicationStatus::Rejected->value,
                    LoanApplicationStatus::Cancelled->value,
                    LoanApplicationStatus::Closed->value,
                ];

                if ($request->boolean('used_for_loan')) {
                    $query->whereHas('loanApplications', fn ($q) => $q->whereNotIn('status', $terminalStatuses));
                } else {
                    $query->whereDoesntHave('loanApplications', fn ($q) => $q->whereNotIn('status', $terminalStatuses));
                }
            }

            $fixed_assests = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'Fixed Assets', 'Fixed assets index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'customer_id', 'is_active']),
                'count' => $fixed_assests->count()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets retrieved successfully',
                'data' => $fixed_assests
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve fixed assets',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function store(CreateFixedAssetsRequest $request)
    {
        try {
            $data = $request->validated();
            $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                ->orWhere('customer_code', $data['customer_id'])
                ->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }
            $fixed_assests = FixedAssests::create($data);

            $this->logActivity('CREATE', 'Fixed Assets', "Created fixed assets: {$fixed_assests->id}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets created successfully',
                'data' => $fixed_assests->load('customer:' . Customer::SUMMARY_COLUMNS),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create fixed assets',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $fixed_assests = FixedAssests::with(['customer:' . Customer::SUMMARY_COLUMNS])->find($id);

            if (!$fixed_assests) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fixed assets not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets retrieved successfully',
                'data' => $fixed_assests
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve fixed assets',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(UpdateFixedAssetsRequest $request, string $id)
    {
        try {
            $fixed_assests = FixedAssests::find($id);

            if (!$fixed_assests) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fixed assets not found'
                ], 404);
            }

            $data = $request->validated();
            $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                ->orWhere('customer_code', $data['customer_id'])
                ->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }
            $fixed_assests->update($data);

            $this->logActivity('Update', 'Fixed Assets', 'Fixed assets updated', [
                'updater_id' => Auth::id(),
                'fixed_asset_id' => $fixed_assests->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets updated successfully',
                'data' => $fixed_assests
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update fixed assets',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $fixed_assests = FixedAssests::find($id);

            if (!$fixed_assests) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fixed assets not found'
                ], 404);
            }

            $fixed_assests->delete();

            $this->logActivity('Delete', 'Fixed Assets', 'Fixed assets deleted (soft)', [
                'deleter_id' => Auth::id(),
                'fixed_asset_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete fixed assets',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $fixed_assests = FixedAssests::find($id);

            if (!$fixed_assests) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fixed assets not found'
                ], 404);
            }

            $fixed_assests->is_active = !$fixed_assests->is_active;
            $fixed_assests->save();

            $this->logActivity('Toggle Status', 'Fixed Assets', 'Fixed assets status toggled', [
                'user_id' => Auth::id(),
                'fixed_asset_id' => $fixed_assests->id,
                'new_status' => $fixed_assests->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets status toggled successfully',
                'data' => $fixed_assests
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle fixed assets status',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    
}
