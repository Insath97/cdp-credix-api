<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLiabilityRequest;
use App\Http\Requests\UpdateLiabilityRequest;
use App\Enums\LoanApplicationStatus;
use App\Models\Customer;
use App\Models\Liability;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LiabilityController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Liability Index', only: ['index', 'show']),
            new Middleware('permission:Liability Create', only: ['store']),
            new Middleware('permission:Liability Update', only: ['update']),
            new Middleware('permission:Liability Delete', only: ['destroy']),
            new Middleware('permission:Liability Toggle Status', only: ['toggleStatus']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Liability::with(['customer:' . Customer::SUMMARY_COLUMNS]);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
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

            $liabilities = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('INDEX', 'Liability', 'Liabilities index accessed', [
                'filters' => $request->only(['search', 'customer_id', 'is_active']),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Liabilities retrieved successfully',
                'data' => $liabilities,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve liabilities',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function store(CreateLiabilityRequest $request)
    {
        try {
            $data = $request->validated();

            $customer = Customer::where('customer_id', $data['customer_id'])
                ->orWhere('customer_code', $data['customer_id'])
                ->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }

            $data['is_active'] = $data['is_active'] ?? true;

            $liability = Liability::create($data);

            $this->logActivity('CREATE', 'Liability', "Created liability: {$liability->id}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Liability created successfully',
                'data' => $liability->load('customer:' . Customer::SUMMARY_COLUMNS),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create liability',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $liability = Liability::with(['customer:' . Customer::SUMMARY_COLUMNS])->find($id);

            if (!$liability) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Liability not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Liability retrieved successfully',
                'data' => $liability,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve liability',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function update(UpdateLiabilityRequest $request, string $id)
    {
        try {
            $liability = Liability::find($id);

            if (!$liability) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Liability not found',
                ], 404);
            }

            $data = $request->validated();

            $customer = Customer::where('customer_id', $data['customer_id'])
                ->orWhere('customer_code', $data['customer_id'])
                ->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }

            $liability->update($data);

            $this->logActivity('UPDATE', 'Liability', "Updated liability: {$liability->id}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Liability updated successfully',
                'data' => $liability->load('customer:' . Customer::SUMMARY_COLUMNS),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update liability',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $liability = Liability::find($id);

            if (!$liability) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Liability not found',
                ], 404);
            }

            $liability->delete();

            $this->logActivity('DELETE', 'Liability', "Deleted liability: {$id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Liability deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete liability',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $liability = Liability::find($id);

            if (!$liability) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Liability not found',
                ], 404);
            }

            $liability->is_active = !$liability->is_active;
            $liability->save();

            $this->logActivity('TOGGLE_STATUS', 'Liability', "Toggled liability status: {$liability->id} (" . ($liability->is_active ? 'Active' : 'Inactive') . ")");

            return response()->json([
                'status' => 'success',
                'message' => 'Liability status updated successfully',
                'data' => [
                    'id' => $liability->id,
                    'is_active' => $liability->is_active,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle liability status',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
