<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LoanRevisionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateLoanRevisionRequest extends FormRequest
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
            'loan_application_id' => 'required|integer|exists:loan_applications,id',
            'revision_type'       => ['required', Rule::enum(LoanRevisionType::class)],
            'reason'              => 'required|string|max:2000',

            // Each revision type is driven by a different input, which is what
            // actually distinguishes them:
            //
            //   reduce_installment -- the officer states the new monthly amount
            //                         the borrower can manage; the term follows.
            //   extend_term        -- the officer states the new term; the
            //                         monthly amount follows.
            //   principal_only     -- the profit is written off and the
            //                         remaining principal is spread over the
            //                         months still left to run, so there is
            //                         nothing for the officer to state. A term
            //                         is still honoured if one is sent.
            'revised_installment_amount' => 'required_if:revision_type,reduce_installment|nullable|numeric|min:0.01',
            'revised_term'               => 'required_if:revision_type,extend_term|nullable|integer|min:1',

            // Proof of the hardship being claimed is what the approver signs
            // off against, so a revision cannot be raised without it.
            'document'            => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
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
