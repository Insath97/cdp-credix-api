<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanApplicationGuarantorRequest extends FormRequest
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
        $id = $this->route('loan_application_guarantor');

        return [
            'loan_application_id' => 'nullable|integer|exists:loan_applications,id',
            'guarantor_id'        => [
                'nullable',
                'integer',
                'exists:guarantors,id',
                Rule::unique('loan_application_guarantors')->where(function ($query) {
                    $loanAppId = $this->loan_application_id ?? optional($this->route('loan_application_guarantor'))->loan_application_id;
                    return $query->where('loan_application_id', $loanAppId);
                })->ignore($id),
            ],
            'guarantor_type'      => 'nullable|string|max:255',
            'status'              => 'nullable|string|in:pending,approved,rejected',
            'remarks'             => 'nullable|string',
        ];
    }

}
