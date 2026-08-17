<?php

namespace App\Http\Controllers\V1;

use App\Traits\ActivityLogTrait;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomerRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerController extends Controller implements HasMiddleware
{
    use ActivityLogTrait;

    public function __construct(protected NotificationService $notificationService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:Customer Index', only: ['index', 'show', 'getPublicDetails']),
            new Middleware('permission:Customer Create', only: ['store']),
            new Middleware('permission:Customer Update', only: ['update']),
            new Middleware('permission:Customer Delete', only: ['destroy']),
            new Middleware('permission:Customer Restore', only: ['restore']),
            new Middleware('permission:Customer Force Delete', only: ['forceDelete']),
            new Middleware('permission:Customer Toggle Status', only: ['toggleStatus']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Customer::with(['user']);

            if ($request->has('search') ) {
                $query->search($request->search);
            }

            // Filter by user (agent) if needed
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            $currentUser = Auth::guard('api')->user();
            if ($currentUser && $currentUser->user_type === 'staff') {
                if ($currentUser->employee && $currentUser->employee->branch_id) {
                    $query->where('branch_id', $currentUser->employee->branch_id);
                }
            }

            $customers = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $this->logActivity('Index', 'Customer', 'Customers index accessed', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['search', 'customer_id', 'branch_id']),
                'count' => $customers->count()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customers retrieved successfully',
                'data' => $customers
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customers',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function store(CreateCustomerRequest $request)
    {
        DB::beginTransaction();
        try {
            $currentUser = Auth::guard('api')->user();
            $data = $request->validated();

            $customer = Customer::create($data);

            if (!empty($data['bank_details'])) {
                foreach ($data['bank_details'] as $bankDetail) {
                    $customer->bankDetails()->create($bankDetail);
                }
            }

            if (!empty($data['fixed_assets'])) {
                foreach ($data['fixed_assets'] as $fixedAsset) {
                    $customer->fixedAssets()->create($fixedAsset);
                }
            }

            if (!empty($data['moving_assets'])) {
                foreach ($data['moving_assets'] as $movingAsset) {
                    $customer->movingAssets()->create($movingAsset);
                }
            }

            if (!empty($data['liabilities'])) {
                foreach ($data['liabilities'] as $liability) {
                    $customer->liabilities()->create($liability);
                }
            }

            $plainPassword = Str::random(10);

            $user = User::create([
                'name' => $customer->full_name,
                'username' => $customer->customer_code,
                'email' => $customer->email,
                'password' => Hash::make($plainPassword),
                'user_type' => 'customer',
                'customer_id' => $customer->id,
                'is_active' => true,
                'can_login' => true,
            ]);
            if (!empty($data['guarantors'])) {
                foreach ($data['guarantors'] as $guarantor) {
                    $customer->guarantors()->create($guarantor);
                }
            }

            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    if (empty($doc['file']) && empty($doc['file_path'])) {
                        continue;
                    }
                    $documentName = $doc['document_name'] ?? ($doc['document_type'] ?? 'Document');
                    $customer->documents()->create([
                        'document_name' => $documentName,
                        'document_type' => $doc['document_type'],
                        'remarks' => $doc['remarks'] ?? null,
                        'is_active' => $doc['is_active'] ?? true,
                        'uploaded_at' => now(),
                        'file_path' => !empty($doc['file'])
                            ? $this->storeDocumentFile($doc['file'], $customer->id, $documentName)
                            : $doc['file_path'],
                    ]);
                }
            }

            DB::commit();

            $customer->load(['bankDetails', 'fixedAssets', 'movingAssets', 'liabilities', 'guarantors', 'documents']);

            $credentialsMessage = "Welcome! Your CDP Credix account has been created.\nUsername: {$user->username}\nPassword: {$plainPassword}\nPlease keep this information secure and change your password after logging in.";

            if (!empty($customer->email)) {
                $this->notificationService->sendEmail(
                    'customer_registration_credentials',
                    $customer->email,
                    'Your CDP Credix Account Credentials',
                    $credentialsMessage,
                    ['customer_id' => $customer->id, 'user_id' => $user->id],
                    'Login credentials email sent to customer.'
                );
            } elseif (!empty($customer->phone_primary)) {
                $this->notificationService->sendSms(
                    'customer_registration_credentials',
                    $customer->phone_primary,
                    $credentialsMessage,
                    ['customer_id' => $customer->id, 'user_id' => $user->id],
                    'Login credentials SMS sent to customer.'
                );
            }

            $this->logActivity('Create', 'Customer', 'Customer created with associated details', [
                'creator_id' => $currentUser ? $currentUser->id : null,
                'customer_code' => $customer->customer_code
            ]);

            $this->logActivity('Create', 'User', "Login account created for customer: {$customer->customer_code}", [
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer and associated details created successfully',
                'data' => $customer
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $customer = Customer::with(['user', 'bankDetails', 'fixedAssets', 'movingAssets', 'liabilities', 'guarantors', 'documents'])->find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Customer retrieved successfully',
                'data' => $customer
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(UpdateCustomerRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            $data = $request->validated();
            $customer->update($data);

            // Bank Details
            $customer->bankDetails()->delete();
            if (!empty($data['bank_details'])) {
                foreach ($data['bank_details'] as $bankDetail) {
                    $customer->bankDetails()->create($bankDetail);
                }
            }

            // Fixed Assets
            $customer->fixedAssets()->delete();
            if (!empty($data['fixed_assets'])) {
                foreach ($data['fixed_assets'] as $fixedAsset) {
                    $customer->fixedAssets()->create($fixedAsset);
                }
            }

            // Moving Assets
            $customer->movingAssets()->delete();
            if (!empty($data['moving_assets'])) {
                foreach ($data['moving_assets'] as $movingAsset) {
                    $customer->movingAssets()->create($movingAsset);
                }
            }

            // Liabilities
            $customer->liabilities()->delete();
            if (!empty($data['liabilities'])) {
                foreach ($data['liabilities'] as $liability) {
                    $customer->liabilities()->create($liability);
                }
            }

            // Guarantors
            $customer->guarantors()->delete();
            if (!empty($data['guarantors'])) {
                foreach ($data['guarantors'] as $guarantor) {
                    $customer->guarantors()->create($guarantor);
                }
            }

            // Documents
            $customer->documents()->delete();
            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    if (empty($doc['file']) && empty($doc['file_path'])) {
                        continue;
                    }
                    $documentName = $doc['document_name'] ?? ($doc['document_type'] ?? 'Document');
                    $customer->documents()->create([
                        'document_name' => $documentName,
                        'document_type' => $doc['document_type'],
                        'remarks' => $doc['remarks'] ?? null,
                        'is_active' => $doc['is_active'] ?? true,
                        'uploaded_at' => now(),
                        'file_path' => !empty($doc['file'])
                            ? $this->storeDocumentFile($doc['file'], $customer->id, $documentName)
                            : $doc['file_path'],
                    ]);
                }
            }

            DB::commit();

            $customer->load(['bankDetails', 'fixedAssets', 'movingAssets', 'liabilities', 'guarantors', 'documents']);

            $this->logActivity('Update', 'Customer', 'Customer updated', [
                'updater_id' => Auth::id(),
                'customer_id' => $customer->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            $customer->delete();

            $this->logActivity('Delete', 'Customer', 'Customer deleted (soft)', [
                'deleter_id' => Auth::id(),
                'customer_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $customer = Customer::withTrashed()->find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            $customer->restore();

            $this->logActivity('Info', 'Customer', 'Customer restored', [
                'restorer_id' => Auth::id(),
                'customer_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer restored successfully',
                'data' => $customer
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDelete(string $id)
    {
        try {
            $customer = Customer::withTrashed()->find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            $customer->forceDelete();

            $this->logActivity('Delete', 'Customer', 'Customer permanently deleted', [
                'deleter_id' => Auth::id(),
                'customer_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer permanently deleted'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete customer',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            $customer->is_active = !$customer->is_active;
            $customer->save();

            $this->logActivity('Toggle Status', 'Customer', 'Customer status toggled', [
                'user_id' => Auth::id(),
                'customer_id' => $customer->id,
                'new_status' => $customer->is_active
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer status updated successfully',
                'data' => [
                    'id' => $customer->id,
                    'is_active' => $customer->is_active
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle customer status',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getPublicDetails($customer_code = null)
    {
        try {
            if ($customer_code) {
                $customer = Customer::with([
                    'bankDetails:id,customer_id,bank_name,branch_name,account_number,payment_method',
                ])
                    ->select('id', 'customer_code', 'full_name', 'email', 'phone_primary', 'id_type', 'id_number')
                    ->where('customer_code', $customer_code)->orderBy('id', 'asc')
                    ->first();

                if (!$customer) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Customer not found'
                    ], 404);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer details retrieved successfully',
                    'data' => $customer
                ], 200);
            }

            // If no customer_code, return all
            $perPage = request()->get('per_page', 15);
            $customers = Customer::with([
                'bankDetails:id,customer_id,bank_name,branch_name,account_number,payment_method',
            ])
                ->select('id', 'customer_code', 'full_name', 'email', 'phone_primary', 'id_type', 'id_number')
                ->orderBy('id', 'asc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'All customer details retrieved successfully',
                'data' => $customers
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer details',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    private function storeDocumentFile($file, int $customerId, ?string $documentName): string
    {
        $directory = 'uploads/documents';

        if (!File::exists(public_path($directory))) {
            File::makeDirectory(public_path($directory), 0755, true);
        }

        $extension = $file->getClientOriginalExtension();
        $clean = preg_replace('/[^\p{L}\p{N}\-_.]+/u', '_', trim($documentName ?? 'document'));
        $clean = trim($clean, '._');
        if ($clean === '') {
            $clean = 'document';
        }
        $clean = mb_substr($clean, 0, 80);

        $fileName = $customerId . '_' . $clean . '.' . $extension;
        $i = 1;
        while (File::exists(public_path($directory . '/' . $fileName))) {
            $fileName = $customerId . '_' . $clean . '_' . (++$i) . '.' . $extension;
        }

        $file->move(public_path($directory), $fileName);

        return $directory . '/' . $fileName;
    }
}
