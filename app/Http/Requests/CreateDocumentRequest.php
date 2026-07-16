<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateDocumentRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'documentable_type' => 'required|string|in:customer,guarantor,application,App\Models\Customer,App\Models\Guarantor,App\Models\Application',
            'documentable_id' => 'required|integer',
            'document_type' => 'required|string|in:nic_copy,passport_copy,driving_license,salary_slip,bank_statement,billing_proof,salary_assignment_letter,employer_letter,photo,other',
            'is_mandatory' => 'nullable|boolean',
            'document_name' => 'required|string|max:255',
            'file' => 'required_without:file_path|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'file_path' => 'required_without:file|string|max:1000',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string|in:active,rejected,expired',
            'is_active' => 'nullable|boolean',
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
