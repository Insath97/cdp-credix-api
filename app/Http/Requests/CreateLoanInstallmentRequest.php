<?php

namespace App\Http\Requests;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateLoanInstallmentRequest extends FormRequest
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
            'loan_application_id'   => [
                'required',
                'integer',
                'exists:loan_applications,id',
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($value);

                    if (!$loanApplication) {
                        return;
                    }

                    // Installments only exist once money is out — the schedule
                    // is generated on the Disbursed transition. Without this
                    // guard, rows could be created against a cancelled,
                    // rejected or not-yet-disbursed loan, and they then showed
                    // up in the installment listing carrying those statuses.
                    $repayable = [
                        LoanApplicationStatus::Disbursed,
                        LoanApplicationStatus::Active,
                        LoanApplicationStatus::Overdue,
                    ];

                    if (!in_array($loanApplication->status, $repayable, true)) {
                        $fail("This loan application is {$loanApplication->status->value}. Installments only exist for a disbursed loan.");
                    }
                },
            ],
            // Which customer owes this installment. A Group Loan member owns
            // their own rows, so a manually created row must say whose it is —
            // otherwise it is invisible to per-member overdue, penalties and
            // notifications.
            'customer_id'           => [
                'nullable',
                'integer',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->input('loan_application_id'));

                    if (!$loanApplication) {
                        return;
                    }

                    $isOnThisLoan = (int) $value === (int) $loanApplication->customer_id
                        || $loanApplication->loanApplicationCustomers()->where('customer_id', $value)->exists();

                    if (!$isOnThisLoan) {
                        $fail('That customer is not on this loan application.');
                    }
                },
            ],
            'installment_no'        => [
                'required',
                'integer',
                'min:1',
                // Scoped by customer as well, matching the DB's
                // loan_app_customer_installment_unique index: on a Group Loan
                // every member has their own installment_no 1.
                Rule::unique('loan_installments')->where(function ($query) {
                    return $query->where('loan_application_id', $this->input('loan_application_id'))
                                 ->where('customer_id', $this->input('customer_id'));
                }),
            ],
            'due_date'               => 'required|date',
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
                'field' => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }
}
