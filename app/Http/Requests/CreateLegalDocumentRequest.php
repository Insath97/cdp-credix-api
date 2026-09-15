<?php

namespace App\Http\Requests;

use App\Enums\LegalDocumentStatus;
use App\Models\LegalDocumentTemplate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_application_id'        => 'required|integer|exists:loan_applications,id',
            'legal_document_template_id' => 'nullable|integer|exists:legal_document_templates,id',
            'document_type'              => ['required', 'string', Rule::in(array_keys(LegalDocumentTemplate::TYPES))],
            'language'                   => ['nullable', 'string', Rule::in(array_keys(LegalDocumentTemplate::LANGUAGES))],

            // Defaults to Pending. The created date and the officer who drew
            // the document up are stamped by the controller when the status
            // first reaches Created -- a caller cannot backdate either.
            'status'                     => ['nullable', 'string', Rule::in(LegalDocumentStatus::values())],

            // Everything the officer types that the loan record cannot supply:
            // agreement place and date, investment and certificate details,
            // guarantor addresses, witnesses, the signing officer.
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
