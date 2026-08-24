<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Traits\ActivityLogTrait;
use App\Traits\MasksSensitiveDataTrait;
use App\Traits\ResolvesAuthenticatedCustomerTrait;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController extends Controller
{
    use ActivityLogTrait, MasksSensitiveDataTrait, ResolvesAuthenticatedCustomerTrait;

    /**
     * Display the authenticated customer's own profile.
     */
    public function show()
    {
        try {
            $customer = $this->myCustomer()->load('bankDetails');

            $this->logActivity('Show', 'CustomerPortal', 'Customer viewed own profile', [
                'user_id' => Auth::id(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile retrieved successfully',
                'data' => $this->shapeProfile($customer),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve profile',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the authenticated customer's own contact/address details.
     * Identity and employment fields are intentionally not editable here.
     */
    public function update(UpdateCustomerProfileRequest $request)
    {
        try {
            $customer = $this->myCustomer();
            $customer->update($request->validated());

            $this->logActivity('UPDATE', 'CustomerPortal', "Customer {$customer->customer_code} updated own profile", [
                'user_id' => Auth::id(),
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $this->shapeProfile($customer->fresh('bankDetails')),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Shape a Customer model into the profile response, masking bank account numbers.
     */
    protected function shapeProfile(\App\Models\Customer $customer): array
    {
        return [
            'full_name' => $customer->full_name,
            'customer_id' => $customer->customer_id,
            'customer_code' => $customer->customer_code,
            'id_type' => $customer->id_type,
            'id_number' => $customer->id_number,
            'date_of_birth' => $customer->date_of_birth,
            'phone_primary' => $customer->phone_primary,
            'phone_secondary' => $customer->phone_secondary,
            'email' => $customer->email,
            'address_line_1' => $customer->address_line_1,
            'address_line_2' => $customer->address_line_2,
            'landmark' => $customer->landmark,
            'city' => $customer->city,
            'state' => $customer->state,
            'country' => $customer->country,
            'postal_code' => $customer->postal_code,
            'employment_status' => $customer->employment_status,
            'occupation' => $customer->occupation,
            'employer_name' => $customer->employer_name,
            'monthly_income' => $customer->monthly_income,
            'business_name' => $customer->business_name,
            'business_registration_number' => $customer->business_registration_number,
            'bank_details' => $customer->bankDetails->map(function ($bankDetail) {
                return [
                    'id' => $bankDetail->id,
                    'bank_name' => $bankDetail->bank_name,
                    'branch_name' => $bankDetail->branch_name,
                    'account_number_masked' => $this->maskTail($bankDetail->account_number),
                    'payment_method' => $bankDetail->payment_method,
                ];
            }),
            'registration_date' => $customer->created_at,
        ];
    }
}
