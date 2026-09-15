<?php

namespace App\Http\Requests;

use App\Enums\LegalDocumentStatus;
use App\Models\LegalDocumentTemplate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_by'                 => 'nullable|integer|exists:users,id',
            'created_at'                 => 'nullable|date',
            'updated_by'                 => 'nullable|integer|exists:users,id',
            'updated_at'                 => 'nullable|date',
            'loan_application_id'        => 'sometimes|required|integer|exists:loan_applications,id',
            'legal_document_template_id' => 'nullable|integer|exists:legal_document_templates,id',
            'document_type'              => ['sometimes', 'required', 'string', Rule::in(array_keys(LegalDocumentTemplate::TYPES))],
            'language'                   => ['nullable', 'string', Rule::in(array_keys(LegalDocumentTemplate::LANGUAGES))],
            'status'                     => ['nullable', 'string', Rule::in(LegalDocumentStatus::values())],
            'created_at'                 => 'nullable|date',
            'updated_by'                 => 'nullable|integer|exists:users,id',
            'updated_at'                 => 'nullable|date',
            'details'                    => 'nullable|array',
            'remarks'                    => 'nullable|string|max:2000',
            'is_active'                  => 'nullable|boolean',
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
