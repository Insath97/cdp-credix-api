<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Guarantor;
use App\Models\Customer;
use App\Enums\LoanApplicationStatus;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Traits\ActivityLogTrait;
use App\Http\Requests\UpdateGuarantorRequest;
use App\Http\Requests\CreateGuarantorRequest;


class GuarantorController extends Controller implements HasMiddleware
{
     use ActivityLogTrait;

    /**
     * HasMiddleware and Middleware were already imported here but the class
     * never implemented the interface, so every /guarantors endpoint sat behind
     * auth:api alone -- any signed-in user could list, create, edit or delete
     * guarantors regardless of their permissions, even though the four
     * permissions below have been seeded all along.
     *
     * toggleStatus is grouped under Update rather than given its own string:
     * 'Guarantor Toggle Status' is not in PermissionsSeeder, and a permission
     * name that does not exist denies everyone except Super Admin.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Guarantor Index',  only: ['index', 'show']),
            new Middleware('permission:Guarantor Create', only: ['store']),
            new Middleware('permission:Guarantor Update', only: ['update', 'toggleStatus']),
            new Middleware('permission:Guarantor Delete', only: ['destroy']),
        ];
    }

     public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Guarantor::with(['customer:'.Customer::SUMMARY_COLUMNS]);

            if ($request->has('search') ) {
                $query->search($request->search);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
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

            $guarantors = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'Guarantor', 'Guarantors index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'customer_id']),
                'count' => $guarantors->count()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantors retrieved successfully',
                'data' => $guarantors
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve guarantors',
                'error' => $th->getMessage()
            ], 500);
        }

    }

     public function store(CreateGuarantorRequest $request)
    {
        try {
            $data = $request->validated();
            $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                ->orWhere('customer_code', $data['customer_id'])
                ->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }
            $guarantor = Guarantor::create($data);

            $this->logActivity('CREATE', 'Guarantor', "Created guarantor: {$guarantor->full_name}", $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor created successfully',
                'data' => $guarantor->load('customer:'.Customer::SUMMARY_COLUMNS),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create guarantor',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

     public function show(string $id)
    {
        try {
            $guarantor = Guarantor::with(['customer:'.Customer::SUMMARY_COLUMNS])->find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor retrieved successfully',
                'data' => $guarantor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve guarantor',
                'error' => $th->getMessage()
            ], 500);
        }
    }

     public function update(UpdateGuarantorRequest $request, string $id)
    {
        try {
            $guarantor = Guarantor::find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            $data = $request->validated();
            $customer = \App\Models\Customer::where('customer_id', $data['customer_id'])
                ->orWhere('customer_code', $data['customer_id'])
                ->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }
            $guarantor->update($data);

            $this->logActivity('Update', 'Guarantor', 'Guarantor updated', [
                'updater_id' => Auth::id(),
                'guarantor_id' => $guarantor->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor updated successfully',
                'data' => $guarantor
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update guarantor',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $guarantor = Guarantor::find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            $guarantor->delete();

            $this->logActivity('Delete', 'Guarantor', 'Guarantor deleted (soft)', [
                'deleter_id' => Auth::id(),
                'guarantor_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete guarantor',
                'error' => $th->getMessage()
            ], 500);
        }
    }

     public function toggleStatus(string $id)
    {
        try {
            $guarantor = Guarantor::find($id);

            if (!$guarantor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Guarantor not found'
                ], 404);
            }

            $guarantor->is_active = !$guarantor->is_active;
            $guarantor->save();

            $this->logActivity('Toggle Status', 'Guarantor', 'Guarantor status toggled', [
                'user_id' => Auth::id(),
                'guarantor_id' => $guarantor->id,
                'new_status' => $guarantor->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantor status updated successfully',
                'data' => [
                    'id' => $guarantor->id,
                    'is_active' => $guarantor->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle guarantor status',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
