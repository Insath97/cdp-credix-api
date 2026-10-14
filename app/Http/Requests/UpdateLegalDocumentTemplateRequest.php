<?php

namespace App\Http\Requests;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentTemplate;
use App\Models\LoanProduct;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateLegalDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** As on create: a loan_product_id stands for that product's loan type. */
    protected function prepareForValidation(): void
    {
        if (!$this->filled('loan_type_id') && $this->filled('loan_product_id')) {
            $loanTypeId = LoanProduct::whereKey($this->input('loan_product_id'))->value('loan_type_id');

            if ($loanTypeId) {
                $this->merge(['loan_type_id' => $loanTypeId]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'loan_type_id'     => 'sometimes|required|integer|exists:loan_types,id',
            'loan_product_id'  => 'nullable|integer|exists:loan_products,id',
            'created_by'       => 'nullable|integer|exists:users,id',
            'created_at'       => 'nullable|date',
            'updated_by'       => 'nullable|integer|exists:users,id',
            'updated_at'       => 'nullable|date',
            // Uniqueness is checked in withValidator(), against the template
            // as it will stand: any of the three may change on its own.
            'document_type'    => ['sometimes', 'required', 'string', Rule::in(LegalDocumentType::values())],
            'language'         => ['nullable', 'string', Rule::in(array_keys(LegalDocumentTemplate::LANGUAGES))],
            'title'            => 'sometimes|required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'file'             => 'nullable|file|mimes:docx,doc,pdf|max:10240',
            'file_path'        => 'nullable|string|max:1000',
            'content'          => 'nullable|string',
            'is_active'        => 'nullable|boolean',

        ];
    }

    /** One template per loan type, document type and language. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $id = $this->route('legal_document_template') ?? $this->route('id');
            $template = LegalDocumentTemplate::find($id);

            if (!$template || $validator->errors()->isNotEmpty()) {
                return;
            }

            $taken = LegalDocumentTemplate::where('id', '!=', $template->id)
                ->where('loan_type_id', $this->input('loan_type_id', $template->loan_type_id))
                ->where('document_type', $this->input('document_type', $template->document_type))
                ->where('language', $this->input('language', $template->language))
                ->exists();

            if ($taken) {
                $validator->errors()->add('document_type', 'This loan type already has a template for this document type and language.');
            }
        });
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
