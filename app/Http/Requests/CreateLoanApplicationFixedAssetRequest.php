<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use App\Models\FixedAssests;
use App\Models\LoanApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLoanApplicationFixedAssetRequest extends FormRequest
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
            'fixed_assest_id'     => [
                'required',
                'integer',
                'exists:fixed_assests,id',
                Rule::unique('loan_application_fixed_assets')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    $fixedAsset = FixedAssests::where('id', $value)
                        ->where('customer_id', $loanApplication->customer_id)
                        ->first();

                    if (!$fixedAsset) {
                        $fail('This asset does not belong to the customer on this loan application.');
                        return;
                    }

                    if ($fixedAsset->used_for_loan) {
                        $fail('This asset is already pledged to an active loan application.');
                    }
                },
            ],
        ];
    }

}
