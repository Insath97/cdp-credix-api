<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCustomerProfileRequest extends FormRequest
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
     * Only contact/address fields are customer-editable. Identity and
     * employment fields (name, id_number, date_of_birth, employer, income,
     * etc.) require staff-side KYC re-verification and are intentionally
     * absent here.
     */
    public function rules(): array
    {
        return [
            'phone_primary'      => 'sometimes|required|string|max:20',
            'phone_secondary'    => 'nullable|string|max:20',
            'email'              => 'sometimes|required|email|max:255',
            'address_line_1'     => 'sometimes|required|string|max:255',
            'address_line_2'     => 'nullable|string|max:255',
            'landmark'           => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:255',
            'state'              => 'nullable|string|max:255',
            'country'            => 'nullable|string|max:255',
            'postal_code'        => 'nullable|string|max:255',
            'have_whatsapp'      => 'nullable|boolean',
            'whatsapp_number'    => 'nullable|string|max:20',
            'preferred_language' => 'nullable|string|max:50',
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
