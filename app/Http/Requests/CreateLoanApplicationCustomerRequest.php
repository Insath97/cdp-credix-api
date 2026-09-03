<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLoanApplicationCustomerRequest extends FormRequest
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
            'loan_application_id' => 'required|integer|exists:loan_applications,id',
            'customer_id'         => [
                'required',
                'integer',
                'exists:customers,id',
                Rule::unique('loan_application_customers')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    if ((int) $value === (int) $loanApplication->customer_id) {
                        $fail('This customer is already the primary applicant on this loan application.');
                        return;
                    }

                    if ($loanApplication->status !== LoanApplicationStatus::Submitted) {
                        $fail('Customers can only be added while the loan application is in Submitted status.');
                    }
                },
            ],
        ];
    }

}
