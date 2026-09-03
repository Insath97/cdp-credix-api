<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLoanApplicationGuarantorRequest extends FormRequest
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
            'guarantor_id'        => [
                'required',
                'integer',
                'exists:guarantors,id',
                Rule::unique('loan_application_guarantors')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    $guarantor = Guarantor::where('id', $value)
                        ->where('customer_id', $loanApplication->customer_id)
                        ->first();

                    if (!$guarantor) {
                        $fail('This guarantor does not belong to the customer on this loan application.');
                        return;
                    }

                    if ($guarantor->used_for_loan) {
                        $fail('This guarantor is already pledged to an active loan application.');
                    }
                },
            ],
            'guarantor_type'      => 'nullable|string|max:255',
            'status'              => 'nullable|string|in:pending,approved,rejected',
            'remarks'             => 'nullable|string',
        ];
    }

}
