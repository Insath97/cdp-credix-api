<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanInstallmentRequest extends FormRequest
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
        $id = $this->route('loan_installment');

        return [
            'loan_application_id'   => 'nullable|integer|exists:loan_applications,id',
            'installment_no'        => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('loan_installments')->where(function ($query) {
                    $loanAppId = $this->loan_application_id ?? optional($this->route('loan_installment'))->loan_application_id;
                    return $query->where('loan_application_id', $loanAppId);
                })->ignore($id),
            ],
            'due_date'               => 'nullable|date',
            'amount_due'             => 'nullable|numeric|min:0',
            'amount_paid'            => 'nullable|numeric|min:0',
            'penalty_amount'         => 'nullable|numeric|min:0',
            'penalty_waived_by'      => 'nullable|integer|exists:users,id',
            'penalty_waived_reason'  => 'nullable|string',
            'balance'                 => 'nullable|numeric|min:0',
            'status'                  => 'nullable|string|in:upcoming,due,partially_paid,paid,overdue,waived,revised',
            'paid_at'                 => 'nullable|date',
        ];
    }

}
