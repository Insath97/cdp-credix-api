<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateLoanInstallmentRequest extends FormRequest
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

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();

        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field'    => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors'  => $fieldErrors,
        ], 422));
    }
}
