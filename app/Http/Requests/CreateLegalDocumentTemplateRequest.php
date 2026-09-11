<?php

namespace App\Http\Requests;

use App\Models\LegalDocumentTemplate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateLegalDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_product_id'  => 'required|integer|exists:loan_products,id',
            // One template per product per type per language. Declared on a
            // field the caller always submits: a closure rule hung on a field
            // that is never sent is skipped by the validator entirely.
            'document_type'    => [
                'required',
                'string',
                Rule::in(array_keys(LegalDocumentTemplate::TYPES)),
                Rule::unique('legal_document_templates', 'document_type')
                    ->where(fn ($query) => $query
                        ->where('loan_product_id', $this->input('loan_product_id'))
                        ->where('language', $this->input('language', 'en'))
                        ->whereNull('deleted_at')),
            ],
            'language'         => ['nullable', 'string', Rule::in(array_keys(LegalDocumentTemplate::LANGUAGES))],
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',

            // The signed-off source document. Optional, because a template can
            // be registered before the legal desk hands over the file.
            'file'             => 'nullable|file|mimes:docx,doc,pdf|max:10240',
            'file_path'        => 'nullable|string|max:1000',
            'content'          => 'nullable|string',
            'is_active'        => 'nullable|boolean',

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
