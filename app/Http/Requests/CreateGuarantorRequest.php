<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGuarantorRequest extends FormRequest
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
            'customer_id' => 'required|string|exists:customers,id',
            'full_name' => 'required|string|max:255',
            'type' => 'required|string|in:guarantor_1,guarantor_2',
            'id_type' => 'required|string|max:100',
            'id_number' => 'required|string|max:100',
            'id_image' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date|before:today',
            'phone_primary' => 'nullable|string|max:20',

            // A guarantor is asked to prove income one of two ways, and the
            // status decides which block is mandatory. Neither block is
            // accepted half-filled: a salary with no employer, or a business
            // name with no registration, is not evidence of anything.
            'employment_status' => 'required|string|in:Employed,Self-Employed',
            'occupation' => 'required_if:employment_status,Employed|nullable|string|max:255',
            'employer_name' => 'required_if:employment_status,Employed|nullable|string|max:255',
            'business_name' => 'required_if:employment_status,Self-Employed|nullable|string|max:255',
            'business_registration_number' => 'required_if:employment_status,Self-Employed|nullable|string|max:255',
            'business_phone' => 'required_if:employment_status,Self-Employed|nullable|string|max:20',

            'date_joined' => 'nullable|date',
            'salary' => 'required_if:employment_status,Employed|nullable|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'other_income' => 'nullable|numeric|min:0',
            'liabilities' => 'nullable|numeric|min:0',
            'bank_name_of_guarantor' => 'nullable|string|max:255',
            'bank_account_no_of_guarantor' => 'nullable|string|max:255',
            'bank_branch_of_guarantor' => 'nullable|string|max:255',
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
