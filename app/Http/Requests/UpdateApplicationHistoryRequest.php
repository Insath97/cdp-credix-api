<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationHistoryRequest extends FormRequest
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
            'application_id' => 'required|exists:applications,id',
            'customer_id' => 'required|string|exists:customers,customer_id',
            'application_no' => 'nullable|string|max:255|unique:applications,application_no',            'application_type' => 'required|string|max:255',
            'role' => 'required|string|in:primary,joint',
            'status' => 'required|string|max:255',

            // Income / Expense 
            'basic_salary' => 'nullable|numeric|min:0',
            'fixed_allowances' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'other_income' => 'nullable|numeric|min:0',
            'total_monthly_income' => 'nullable|numeric|min:0',
            'household_expenses' => 'nullable|numeric|min:0',
            'rent_expense' => 'nullable|numeric|min:0',
            'insurance_premiums' => 'nullable|numeric|min:0',
            'other_expenses' => 'nullable|numeric|min:0',
            'total_monthly_expenses' => 'nullable|numeric|min:0',
            'requested_amount' => 'nullable|numeric|min:0',
            'purpose' => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ];
    }

}
