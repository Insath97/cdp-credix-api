<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLoanApplicationRequest extends FormRequest
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
            'application_id'         => 'nullable|integer|exists:applications,id',
            'customer_id'            => 'nullable|integer|exists:customers,id',
            'loan_product_id'        => 'nullable|integer|exists:loan_products,id',
            'branch_id'              => 'nullable|integer|exists:branches,id',
            'requested_amount'       => 'nullable|numeric|min:0',
            'interest_rate'          => 'nullable|numeric|min:0|max:999.999',
            'interest_type'          => 'nullable|string|max:255',
            'term_months'            => 'nullable|integer|min:1',
            'monthly_installment'    => 'nullable|numeric|min:0',
            'processing_fee'         => 'nullable|numeric|min:0',
            'monthly_repayment_date' => 'nullable|string|max:255',
            'applied_by'             => 'nullable|integer|exists:users,id',
            'applied_at'             => 'nullable|date',
            'outstanding_balance'    => 'nullable|numeric|min:0',
            'is_active'              => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();

        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field'    => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors'  => $fieldErrors,
        ], 422));
    }
}
