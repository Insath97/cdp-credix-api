<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSignatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'               => 'required|exists:customers,id',

            // Ensure the main JSON wrapper isn't bloated
            'signature_data'            => 'required|array|max:2000',
            'signature_data.width'      => 'required|integer|min:100|max:3000',
            'signature_data.height'     => 'required|integer|min:100|max:3000',

            // Ensure lines is an array and doesn't exceed 500 strokes
            'signature_data.lines'      => 'required|array|min:1|max:500',

            // Each stroke line must contain at least a start point and an end point
            'signature_data.lines.*'    => 'required|array|min:2',
            'signature_data.lines.*.*.x' => 'required|numeric',
            'signature_data.lines.*.*.y' => 'required|numeric',
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
