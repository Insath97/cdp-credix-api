<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateLoanProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:loan_products,code',
            'loan_type_id' => 'required|integer|exists:loan_types,id',
            'loan_term_id' => 'required|integer|exists:loan_terms,id',
            'description' => 'nullable|string',
            'interest_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99.999',
                Rule::requiredIf(fn () => !$this->boolean('is_group_loan')),
            ],
            'interest_type' => 'nullable|string|in:flat,reducing',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0|gte:min_amount',
            'min_term_months' => 'required|integer|min:1',
            'max_term_months' => 'required|integer|min:1|gte:min_term_months',
            'processing_fee_type' => 'required|string|in:fixed,percentage',
            'processing_fee_value' => 'required|numeric|min:0',
            'penalty_value' => 'nullable|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_islamic' => 'nullable|boolean',
            'is_group_loan' => 'nullable|boolean',
            'requires_investment_collateral' => 'nullable|boolean',
            'max_loan_percentage' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:100',
                Rule::requiredIf(fn () => $this->boolean('requires_investment_collateral')),
                Rule::prohibitedIf(fn () => $this->has('requires_investment_collateral') && !$this->boolean('requires_investment_collateral')),
            ],
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
