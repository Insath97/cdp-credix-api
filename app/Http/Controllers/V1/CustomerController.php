<?php

namespace App\Http\Controllers\V1;

use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use App\Traits\ScopesToUserBranch;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomerRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Employee;
use App\Models\LoanApplication;
use App\Models\LoanApplicationStatusHistory;
use App\Models\User;
use App\Enums\LoanApplicationStatus;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait, ScopesToUserBranch;

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
            $query = Customer::with(['user', 'customerDetail']);

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

            $this->scopeToUserBranch($query);

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

            $data = Employee::mergeRecommenderSnapshot($data);

            $customer = Customer::create($data);

            if (array_filter($data, fn ($key) => in_array($key, ['gn_division', 'ds_division', 'district', 'province']) && !empty($data[$key]), ARRAY_FILTER_USE_KEY)) {
                $customer->customerDetail()->create([
                    'gn_division' => $data['gn_division'] ?? null,
                    'ds_division' => $data['ds_division'] ?? null,
                    'district'    => $data['district'] ?? null,
                    'province'    => $data['province'] ?? null,
                ]);
            }

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

            $plainPassword = User::generateTemporaryPassword();
            $user = User::create([
                'name' => $customer->full_name,
                'username' => !empty($data['create_user_account']) && !empty($data['user_username'])
                    ? $data['user_username']
                    : $customer->customer_code,
                'email' => $customer->email,
                'password' => Hash::make($plainPassword),
                'password_changed_at' => null,
                'password_expires_at' => now()->addDays(User::TEMPORARY_PASSWORD_DAYS),
                'user_type' => 'customer',
                'customer_id' => $customer->id,
                'is_active' => true,
                'can_login' => true,
            ]);

            if (!empty($data['guarantors'])) {
                foreach ($data['guarantors'] as $guarantorData) {
                    // Documents ride in on the guarantor payload but belong to
                    // the documents table, so they are pulled out before the
                    // guarantor row is written.
                    $guarantorDocuments = $guarantorData['documents'] ?? [];
                    unset($guarantorData['documents']);

                    $guarantor = $customer->guarantors()->create($guarantorData);

                    foreach ($guarantorDocuments as $doc) {
                        if (empty($doc['file']) && empty($doc['file_path'])) {
                            continue;
                        }

                        $documentName = $doc['document_name'] ?? ($doc['document_type'] ?? 'Document');

                        Document::create([
                            'document_name' => $documentName,
                            'document_type' => $doc['document_type'] ?? null,
                            'remarks'       => $doc['remarks'] ?? null,
                            'is_active'     => $doc['is_active'] ?? true,
                            'uploaded_at'   => now(),
                            'guarantor_id'  => $guarantor->id,
                            'customer_id'   => $customer->id,
                            'file_path'     => !empty($doc['file'])
                                ? $this->storeUploadedFile($doc['file'], 'documents', $customer->id . '_' . $documentName)
                                : $doc['file_path'],
                        ]);
                    }
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
                            ? $this->storeUploadedFile($doc['file'], 'documents', $customer->id . '_' . $documentName)
                            : $doc['file_path'],
                    ]);
                }
            }

            if (!empty($data['loan_product_id'])) {
                $branchName = !empty($data['branch_id'])
                    ? \App\Models\Branch::find($data['branch_id'])?->name
                    : null;

                $application = Application::create([
                    'application_type' => 'loan',
                    'branch' => $branchName,
                    'requested_amount' => $data['requested_amount'] ?? null,
                    'repayment_period_months' => $data['term_months'] ?? null,
                    'monthly_repayment_date' => $data['monthly_repayment_date'] ?? null,
                ]);

                $loanApplication = LoanApplication::create([
                    'application_id' => $application->id,
                    'customer_id' => $customer->id,
                    'loan_product_id' => $data['loan_product_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'requested_amount' => $data['requested_amount'] ?? null,
                    'interest_rate' => $data['interest_rate'] ?? null,
                    'interest_type' => $data['interest_type'] ?? 'flat',
                    'term_months' => $data['term_months'] ?? null,
                    'monthly_repayment_date' => $data['monthly_repayment_date'] ?? null,
                    'applied_by' => Auth::id(),
                    'applied_at' => now(),
                    'status' => LoanApplicationStatus::Submitted->value,
                    'is_active' => true,
                ]);

                LoanApplicationStatusHistory::record(
                    $loanApplication,
                    LoanApplicationStatus::Submitted,
                    Auth::id(),
                    'Loan application submitted at customer registration'
                );
            }

            DB::commit();

            $customer->load(['customerDetail', 'bankDetails', 'fixedAssets', 'movingAssets', 'liabilities', 'guarantors', 'documents']);

            $credentialsMessage = "Welcome! Your account has been created.\n"
                . "Username: {$user->username}\n"
                . "Temporary Password: {$plainPassword}\n"
                . 'This password must be changed within ' . User::TEMPORARY_PASSWORD_DAYS . ' days.';

            if (!empty($customer->email)) {
                $emailNotification = $this->notificationService->sendEmail(
                    'customer_registration_credentials',
                    $customer->email,
                    'Your CDP Capital Account Credentials',
                    $credentialsMessage,
                    ['customer_id' => $customer->id, 'user_id' => $user->id],
                    'Login credentials email sent to customer.'
                );

                if ($emailNotification->status === 'sent') {
                    $this->logActivity('EMAIL_SENT', 'Customer', "Registration credentials email sent to customer: {$customer->email}", [
                        'customer_id'     => $customer->id,
                        'email'           => $customer->email,
                        'notification_id' => $emailNotification->id,
                    ]);
                } else {
                    $this->logActivity('EMAIL_FAILED', 'Customer', "Failed to send registration credentials email to customer: {$customer->email}", [
                        'customer_id'     => $customer->id,
                        'email'           => $customer->email,
                        'notification_id' => $emailNotification->id,
                        'error'           => $emailNotification->error,
                    ], 'error');
                }
            } elseif (!empty($customer->phone_primary)) {
                $smsNotification = $this->notificationService->sendSms(
                    'customer_registration_credentials',
                    $customer->phone_primary,
                    $credentialsMessage,
                    ['customer_id' => $customer->id, 'user_id' => $user->id],
                    'Login credentials SMS sent to customer.'
                );

                if ($smsNotification->status !== 'failed') {
                    $this->logActivity('SMS_QUEUED', 'Customer', "Registration credentials SMS queued for customer: {$customer->phone_primary}", [
                        'customer_id'     => $customer->id,
                        'phone'           => $customer->phone_primary,
                        'notification_id' => $smsNotification->id,
                    ]);
                } else {
                    $this->logActivity('SMS_FAILED', 'Customer', "Failed to queue registration credentials SMS for customer: {$customer->phone_primary}", [
                        'customer_id'     => $customer->id,
                        'phone'           => $customer->phone_primary,
                        'notification_id' => $smsNotification->id,
                        'error'           => $smsNotification->error,
                    ], 'error');
                }
            } else {
                $this->logActivity('NOTIFICATION_SKIPPED', 'Customer', "No email or primary phone available to send registration credentials for customer: {$customer->customer_code}", [
                    'customer_id' => $customer->id,
                ], 'warning');
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
            $customer = Customer::with(['user', 'customerDetail', 'bankDetails', 'fixedAssets', 'movingAssets', 'liabilities', 'guarantors', 'documents'])->find($id);

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
        // The lookup happens BEFORE the transaction opens.
        //
        // Opening it first meant the not-found branch returned its 404 without
        // ever rolling back, leaving an open transaction -- and its locks --
        // held for the rest of the request. Nothing here needs a transaction in
        // order to decide whether the row exists.
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            // Keep the recommender snapshot in lock-step with the chosen
            // employee (see Employee::mergeRecommenderSnapshot).
            $data = Employee::mergeRecommenderSnapshot($data);

            $customer->update($data);

            if (array_filter($data, fn ($key) => in_array($key, ['gn_division', 'ds_division', 'district', 'province']) && !empty($data[$key]), ARRAY_FILTER_USE_KEY)) {
                $customer->customerDetail()->updateOrCreate([], [
                    'gn_division' => $data['gn_division'] ?? null,
                    'ds_division' => $data['ds_division'] ?? null,
                    'district'    => $data['district'] ?? null,
                    'province'    => $data['province'] ?? null,
                ]);
            }

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
            //
            // Only replaced when the caller actually sent a guarantors block.
            // Guarantors are collected on the loan application now, not on the
            // customer form, so an edit that says nothing about them must not
            // touch them: deleting a guarantor cascades to its
            // loan_application_guarantors links and to its documents, which
            // silently stripped the guarantors off live loan applications
            // every time someone renamed a customer.
            if (array_key_exists('guarantors', $data)) {
                $customer->guarantors()->delete();
            }
            if (!empty($data['guarantors'])) {
                foreach ($data['guarantors'] as $guarantorData) {
                    // Documents ride in on the guarantor payload but belong to
                    // the documents table, so they are pulled out before the
                    // guarantor row is written.
                    $guarantorDocuments = $guarantorData['documents'] ?? [];
                    unset($guarantorData['documents']);

                    $guarantor = $customer->guarantors()->create($guarantorData);

                    foreach ($guarantorDocuments as $doc) {
                        if (empty($doc['file']) && empty($doc['file_path'])) {
                            continue;
                        }

                        $documentName = $doc['document_name'] ?? ($doc['document_type'] ?? 'Document');

                        Document::create([
                            'document_name' => $documentName,
                            'document_type' => $doc['document_type'] ?? null,
                            'remarks'       => $doc['remarks'] ?? null,
                            'is_active'     => $doc['is_active'] ?? true,
                            'uploaded_at'   => now(),
                            // Both ids: the guarantor owns the document, and
                            // the customer is how it is reached from the
                            // customer's file. customer_id alone would make a
                            // guarantor's papers indistinguishable from the
                            // borrower's own.
                            'guarantor_id'  => $guarantor->id,
                            'customer_id'   => $customer->id,
                            'file_path'     => !empty($doc['file'])
                                ? $this->storeUploadedFile($doc['file'], 'documents', $customer->id . '_' . $documentName)
                                : $doc['file_path'],
                        ]);
                    }
                }
            }

            // Documents
            //
            // Only the customer's own standalone papers are managed by this
            // form. Documents collected against a loan application or a
            // guarantor are owned by the application's document screen and
            // must survive a customer edit -- the form never sends them, so an
            // unscoped delete threw them away.
            $customer->documents()
                ->whereNull('loan_application_id')
                ->whereNull('guarantor_id')
                ->delete();
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
                            ? $this->storeUploadedFile($doc['file'], 'documents', $customer->id . '_' . $documentName)
                            : $doc['file_path'],
                    ]);
                }
            }

            DB::commit();

            $customer->load(['customerDetail', 'bankDetails', 'fixedAssets', 'movingAssets', 'liabilities', 'guarantors', 'documents']);

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
                'bankDetails:id,
                 customer_id,
                 bank_name,
                 branch_name,
                 account_number,
                 payment_method',
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

}
