<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class CreateLoanProductRequest extends FormRequest
{
    use FriendlyValidationErrors;

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
            'loan_term_id' => 'nullable|integer|exists:loan_terms,id',
            'description' => 'nullable|string',
            'interest_rate' => 'required|numeric|min:0|max:999.999',
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
        ];
    }

}
