<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'customer_id'=>'required|string|max:255|unique:customers,customer_id',
        'full_name'=>'required|string|max:500',
        'name_with_initials'=>'required|string|max:255',
        'customer_code' => 'nullable|string',
        'id_type'=>'required',
        'id_number'=>'required',
        'address_line_1'=>'required',
        'address_line_2'=>'required',
        'landmark'=>'required|string|max:255',
        'city'=>'required|string|max:255',
        'state'=>'required|string|max:255',
        'country'=>'required|string|max:255',
        'postal_code'=>'required|string|max:255',
        'date_of_birth'=>'required|date',
        'phone_primary'=>'required|string|max:20',
        'phone_secondary'=>'required|string|max:20',
        'email'=>'required|string|max:255',
        'have_whatsapp'=>'required|boolean',
        'whatsapp_number'=>'required|string|max:20',
        'preferred_language'=>'required|string|max:50',
        'employment_status'=>'required|string|max:50',
        'occupation'=>'required|string|max:255',
        'employer_name'=>'nullable|string|max:255',
        'employer_address_line1'=>'nullable',
        'employer_address_line2'=>'nullable',
        'employer_city'=>'nullable|string|max:255',
        'employer_state'=>'nullable|string|max:255',
        'employer_country'=>'nullable|string|max:255',
        'employer_postal_code'=>'nullable|string|max:255',
        'employer_phone'=>'nullable|string|max:20',
        'employer_email'=>'nullable|string|max:255',
        'business_name'=>'nullable|string|max:255',
        'business_registration_number'=>'nullable|string|max:255',
        'business_address_line1'=>'nullable|string|max:255',
        'business_address_line2'=>'nullable|string|max:255',
        'business_city'=>'nullable|string|max:255',
        'business_state'=>'nullable|string|max:255',
        'business_country'=>'nullable|string|max:255',
        'business_postal_code'=>'nullable|string|max:255',
        'business_phone'=>'nullable|string|max:20',
        'business_email'=>'nullable|string|max:255',
        'branch_id'=>'required|exists:branches,id',
        'is_active'=>'required|boolean',

        // Bank Details Validation
        'bank_details' => 'nullable|array',
        'bank_details.*.bank_name' => 'required_with:bank_details|string|max:255',
        'bank_details.*.branch_name' => 'nullable|string|max:255',
        'bank_details.*.account_number' => 'required_with:bank_details|string|max:255',
        'bank_details.*.payment_method' => 'nullable|string|max:255',
        'bank_details.*.is_active' => 'nullable|boolean',

        // Fixed Assets Validation
        'fixed_assets' => 'nullable|array',
        'fixed_assets.*.owner_name' => 'required_with:fixed_assets|string|max:255',
        'fixed_assets.*.property_location' => 'nullable|string|max:255',
        'fixed_assets.*.extent' => 'nullable|string|max:255',
        'fixed_assets.*.market_value' => 'nullable|numeric|min:0',
        'fixed_assets.*.is_mortaged' => 'nullable|boolean',
        'fixed_assets.*.is_active' => 'nullable|boolean',

        // Moving Assets Validation
        'moving_assets' => 'nullable|array',
        'moving_assets.*.assest_category' => 'required_with:moving_assets|string|max:255',
        'moving_assets.*.owner_name' => 'required_with:moving_assets|string|max:255',
        'moving_assets.*.no_of_shares' => 'nullable|integer|min:0',
        'moving_assets.*.par_value' => 'nullable|numeric|min:0',
        'moving_assets.*.registation_no' => 'nullable|string|max:255',
        'moving_assets.*.market_value' => 'nullable|numeric|min:0',
        'moving_assets.*.mortgage_lease_hire_status' => 'nullable|string|max:255',
        'moving_assets.*.is_active' => 'nullable|boolean',
        ];
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
