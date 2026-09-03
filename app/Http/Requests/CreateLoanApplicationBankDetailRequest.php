<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use App\Models\CustomerBankDetail;
use App\Models\LoanApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLoanApplicationBankDetailRequest extends FormRequest
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
            'loan_application_id'     => 'required|integer|exists:loan_applications,id',
            'customer_bank_detail_id' => [
                'required',
                'integer',
                'exists:customer_bank_details,id',
                Rule::unique('loan_application_bank_details')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    $bankDetail = CustomerBankDetail::where('id', $value)
                        ->where('customer_id', $loanApplication->customer_id)
                        ->first();

                    if (!$bankDetail) {
                        $fail('This bank detail does not belong to the customer on this loan application.');
                    }
                },
            ],
        ];
    }

}
