<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

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
            'customer_id'   => 'nullable|integer|exists:customers,id',
            'loan_application_id' => 'nullable|integer|exists:loan_applications,id',
            'guarantor_id'        => 'nullable|integer|exists:guarantors,id',
            'document_type' => ['nullable', 'string', Rule::in(array_keys(Document::TYPES))],
            'is_mandatory'  => 'nullable|boolean',
            'document_name' => 'required|string|max:255',
            // An upload, and only an upload.
            //
            // file_path used to be accepted here as an alternative, and it is
            // the string every later read, stream and delete resolves against.
            // A caller who could set it could choose which file the server
            // opened: pointed at ../.env against a victim's customer_id, the
            // victim's own portal would stream it straight back. The path is
            // now always server-generated.
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'remarks'       => 'nullable|string',
            'status'        => 'nullable|string|in:active,rejected,expired',
            'is_active'     => 'nullable|boolean',
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
