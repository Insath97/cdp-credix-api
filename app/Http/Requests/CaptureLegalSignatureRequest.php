<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CaptureLegalSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The line being signed, as GET .../signatures lists it: borrower, guarantor_1, witness_2 ...
            'signer'    => 'required|string|max:40',

            // The drawing from the signature pad: a PNG/JPEG upload, or a data:image/...;base64 URI.
            // Content, size and blankness are checked by LegalDocumentSignatureService.
            'signature' => $this->hasFile('signature')
                ? ['required', 'file', 'mimes:png,jpg,jpeg', 'max:1024']
                : ['required', 'string', 'max:1500000'],
        ];
    }

    public function messages(): array
    {
        return [
            'signer.required'    => 'Choose the signature line being signed.',
            'signature.required' => 'Draw the signature before saving it.',
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
