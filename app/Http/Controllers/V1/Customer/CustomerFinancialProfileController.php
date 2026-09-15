<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Traits\ActivityLogTrait;
use App\Traits\MasksSensitiveDataTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Support\Facades\Auth;

class CustomerFinancialProfileController extends Controller
{
    use ActivityLogTrait, MasksSensitiveDataTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * List the customer's guarantors, with which of their loans each backs.
     */
    public function guarantors()
    {
        try {
            $customer = $this->myCustomer();

            $guarantors = $customer->guarantors()
                ->with(['loanApplications' => function ($query) {
                    $query->select('loan_applications.id', 'loan_applications.application_id')
                      ->with('application:id,application_no');
                }])
                ->get()
                ->map(function ($guarantor) {
                    return [
                        'id' => $guarantor->id,
                        'full_name' => $guarantor->full_name,
                        'relationship' => $guarantor->type,
                        'contact_masked' => $this->maskTail($guarantor->phone_primary),
                        'related_loans' => $guarantor->loanApplications->map(fn ($loanApplication) => [
                            'loan_application_id' => $loanApplication->id,
                            'application_no' => $loanApplication->application?->application_no,
                        ]),
                    ];
                });

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed guarantors', [
                'user_id' => Auth::id(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Guarantors retrieved successfully',
                'data' => $guarantors,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve guarantors',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List the customer's declared fixed assets.
     */
    public function fixedAssets()
    {
        try {
            $customerId = $this->myCustomerId();

            $assets = $this->myCustomer()->fixedAssets()->get()->map(fn ($asset) => [
                'id' => $asset->id,
                'owner_name' => $asset->owner_name,
                'property_location' => $asset->property_location,
                'extent' => $asset->extent,
                'market_value' => $asset->market_value,
                'is_mortaged' => $asset->is_mortaged,
                'used_for_loan' => $asset->used_for_loan,
            ]);

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed fixed assets', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fixed assets retrieved successfully',
                'data' => $assets,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve fixed assets',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List the customer's declared moving assets.
     */
    public function movingAssets()
    {
        try {
            $customerId = $this->myCustomerId();

            $assets = $this->myCustomer()->movingAssets()->get()->map(fn ($asset) => [
                'id' => $asset->id,
                'assest_category' => $asset->assest_category,
                'owner_name' => $asset->owner_name,
                'make_model' => $asset->make_model,
                'market_value' => $asset->market_value,
                'mortgage_lease_hire_status' => $asset->mortgage_lease_hire_status,
                'used_for_loan' => $asset->used_for_loan,
            ]);

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed moving assets', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Moving assets retrieved successfully',
                'data' => $assets,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve moving assets',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List the customer's declared liabilities.
     */
    public function liabilities()
    {
        try {
            $customerId = $this->myCustomerId();

            $liabilities = $this->myCustomer()->liabilities()->get()->map(fn ($liability) => [
                'id' => $liability->id,
                'liability_type' => $liability->liability_type,
                'institution_name' => $liability->institution_name,
                'outstanding_balance' => $liability->outstanding_balance,
                'monthly_installment' => $liability->monthly_installment,
                'used_for_loan' => $liability->used_for_loan,
            ]);

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed liabilities', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
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

    /**
     * List the customer's registered bank details, with masked account numbers.
     */
    public function bankDetails()
    {
        try {
            $customerId = $this->myCustomerId();

            $bankDetails = $this->myCustomer()->bankDetails()->get()->map(fn ($bankDetail) => [
                'id' => $bankDetail->id,
                'bank_name' => $bankDetail->bank_name,
                'branch_name' => $bankDetail->branch_name,
                'account_number_masked' => $this->maskTail($bankDetail->account_number),
                'payment_method' => $bankDetail->payment_method,
                'is_active' => $bankDetail->is_active,
                'used_for_loan' => $bankDetail->used_for_loan,
            ]);

            $this->logActivity('Index', 'CustomerPortal', 'Customer viewed bank details', [
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bank details retrieved successfully',
                'data' => $bankDetails,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve bank details',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
