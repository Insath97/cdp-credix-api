<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CreateCustomerBankDetailRequest;
use App\Http\Requests\UpdateCustomerBankDetailRequest;
use App\Models\CustomerBankDetail;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use App\Traits\ActivityLogTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerBankDetailController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Customer Bank Detail Index', only: ['index', 'show', 'bankOptions']),
            new Middleware('permission:Customer Bank Detail Create', only: ['store']),
            new Middleware('permission:Customer Bank Detail Update', only: ['update']),
            new Middleware('permission:Customer Bank Detail Delete', only: ['destroy']),
            new Middleware('permission:Customer Bank Detail Toggle Status', only: ['toggleStatus'])
        ];
    }

    /**
     * Display the configurable list of bank names for the customer bank
     * details dropdown (managed via System Settings, key: customer_bank_list).
     */
    public function bankOptions()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Bank options retrieved successfully',
            'data'    => Setting::get('customer_bank_list', []),
        ], 200);
    }
    
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = CustomerBankDetail::with(['customer']);

            if ($request->has('search') ) {
                $query->search($request->search);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $customerbankdetail = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $customerbankdetail,
                'message' => 'Customer bank details retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'An error occurred while fetching customer bank details.',
            ], 500);
        }
    }

    /**
     * Store a newly created customer bank detail in storage.
     */
    public function store(CreateCustomerBankDetailRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $customer = Customer::where('customer_id', $data['customer_id'])->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }
            $customerbankdetail = CustomerBankDetail::create($data);

            DB::commit();

            $this->logActivity('CREATE', 'Customer bank detail', "Created Customer bank detail: {$customerbankdetail->bank_name} - {$customerbankdetail->account_number}");
            return response()->json([
                'status' => 'success',
                'message' => 'Customer bank detail created successfully',
                'data' => $customerbankdetail
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Customer bank detail',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
            
    }

    /**
     * Display the specified customer bank detail.
     */
    public function show(string $id)
    {
        try {
            $customerbankdetail = CustomerBankDetail::with(['customer'])->find($id);

            if (!$customerbankdetail) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer bank detail not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Customer bank detail retrieved successfully',
                'data' => $customerbankdetail
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Customer bank detail',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(UpdateCustomerBankDetailRequest $request, string $id)
    {
        try {
            $customerbankdetail = CustomerBankDetail::find($id);

            if (!$customerbankdetail) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer bank detail not found'
                ], 404);
            }

            $data = $request->validated();
            $customer = Customer::where('customer_id', $data['customer_id'])->first();
            if ($customer) {
                $data['customer_id'] = $customer->id;
            }
            $customerbankdetail->update($data);

            $this->logActivity('Update', 'Customer bank detail', 'Customer bank detail updated', [
                'updater_id' => Auth::id(),
                'customer_id' => $customerbankdetail->customer_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer bank detail updated successfully',
                'data' => $customerbankdetail
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Customer bank detail',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $customerbankdetail = CustomerBankDetail::find($id);

            if (!$customerbankdetail) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer bank detail not found'
                ], 404);
            }

            $customerbankdetail->delete();

            $this->logActivity('Delete', 'Customer bank detail', 'Customer bank detail deleted', [
                'deleter_id' => Auth::id(),
                'customer_id' => $customerbankdetail->customer_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer bank detail deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Customer bank detail',
                'error' => $th->getMessage()
            ], 500);
        }
    }

        public function toggleStatus(string $id)
    {
        try {
            $customerbankdetail = CustomerBankDetail::find($id);

            if (!$customerbankdetail) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer bank detail not found'
                ], 404);
            }

            $customerbankdetail->is_active = !$customerbankdetail->is_active;
            $customerbankdetail->save();

            $this->logActivity('Toggle Status', 'Customer bank detail', 'Customer bank detail status toggled', [
                'user_id' => Auth::id(),
                'customer_id' => $customerbankdetail->customer_id,
                'new_status' => $customerbankdetail->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer bank detail status updated successfully',
                'data' => [
                    'id' => $customerbankdetail->id,
                    'is_active' => $customerbankdetail->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle customer bank detail status',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}