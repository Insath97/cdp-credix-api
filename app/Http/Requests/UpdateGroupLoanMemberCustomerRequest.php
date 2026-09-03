<?php

namespace App\Http\Requests;

use App\Models\LoanApplication;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Field validation for editing a group loan member's personal details from
 * the group loan screen.
 *
 * The two gates that decide whether the edit is allowed at all — the group
 * loan must still be Available, and this must be the customer's only loan,
 * since these fields live on the shared `customers` record — are enforced in
 * GroupLoanMemberController::updateCustomer(). They live there rather than in
 * a closure rule because a closure on a field the caller never submits is
 * skipped by the validator.
 */
class UpdateGroupLoanMemberCustomerRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $member = LoanApplication::with('customer')->find($this->route('id'));
        $customerId = $member?->customer_id;

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

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();
        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field' => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }

}
