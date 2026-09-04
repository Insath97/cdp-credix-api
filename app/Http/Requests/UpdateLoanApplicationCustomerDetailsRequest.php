<?php

namespace App\Http\Requests;

use App\Models\LoanApplicationCustomer;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Field validation for editing an attached customer's personal details from
 * the loan screen — a Group Loan member, or a Joint Loan co-borrower.
 *
 * The two gates that decide whether the edit is allowed at all — the group
 * loan must still be Available (a joint loan, still Submitted), and this must
 * be the customer's only loan, since these fields live on the shared
 * `customers` record — are enforced in
 * LoanApplicationCustomerController::updateCustomerDetails(). They live there
 * rather than in a closure rule because a closure on a field the caller never
 * submits is skipped by the validator.
 */
class UpdateLoanApplicationCustomerDetailsRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $record = LoanApplicationCustomer::find($this->route('id'));
        $customerId = $record?->customer_id;

        return [
            'full_name'          => 'sometimes|required|string|max:500',
            'name_with_initials' => 'sometimes|required|string|max:255',
            'id_type'            => 'sometimes|required|string|max:100',
            'id_number'          => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('customers', 'id_number')->ignore($customerId),
            ],
            'date_of_birth'   => 'sometimes|required|date',
            'phone_primary'   => 'sometimes|required|string|max:20',
            'phone_secondary' => 'sometimes|nullable|string|max:20',
            'email'           => 'sometimes|required|email|max:255',
            'address_line_1'  => 'sometimes|required|string|max:255',
            'address_line_2'  => 'sometimes|nullable|string|max:255',
            'city'            => 'sometimes|nullable|string|max:255',
            'postal_code'     => 'sometimes|nullable|string|max:255',

            // customer_details supplement (one row per customer).
            'gn_division' => 'sometimes|nullable|string|max:255',
            'ds_division' => 'sometimes|nullable|string|max:255',
            'district'    => 'sometimes|nullable|string|max:255',
            'province'    => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'id_number.unique' => 'Another customer is already registered with this NIC/ID number.',
        ];
    }

    public function attributes(): array
    {
        return ['id_number' => 'NIC/ID number'];
    }
}
