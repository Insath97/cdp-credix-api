<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLiabilityRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|string|exists:customers,customer_id',
            'liability_type' => 'required|in:bank_loan,leasing,credit_card,hire_purchase,other',
            'institution_name' => 'required|string|max:255',
            'account_reference_no' => 'nullable|string|max:255',
            'original_amount' => 'nullable|numeric|min:0',
            'outstanding_balance' => 'required|numeric|min:0',
            'monthly_installment' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ];
    }

}
