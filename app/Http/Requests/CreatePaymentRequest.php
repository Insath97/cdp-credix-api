<?php

namespace App\Http\Requests;

use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class CreatePaymentRequest extends FormRequest
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
            'loan_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('loan_installments', 'id')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $installment = LoanInstallment::find($value);

                    if (!$installment) {
                        return;
                    }

                    // A month already settled in full must never take another
                    // payment. The picker endpoint filters these out, but the
                    // rule is enforced here so it holds no matter what the
                    // caller sends.
                    if ($installment->isSettled()) {
                        $fail("Installment #{$installment->installment_no} is already settled ({$installment->status}) and cannot take another payment. Choose a month that still has a balance.");
                    }
                },
            ],
            // Which member of a Group Loan handed the money over. Optional and
            // purely attribution — the installments and outstanding balance
            // stay group-level either way.
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    $isOnThisLoan = (int) $value === (int) $loanApplication->customer_id
                        || $loanApplication->loanApplicationCustomers()->where('customer_id', $value)->exists();

                    if (!$isOnThisLoan) {
                        $fail('That customer is not on this loan, so the payment cannot be attributed to them.');
                        return;
                    }

                    // A Group Loan member owns their own installments, so a
                    // receipt cannot be booked against someone else's row.
                    if ($this->filled('loan_installment_id')) {
                        $installment = LoanInstallment::find($this->input('loan_installment_id'));

                        if ($installment && $installment->customer_id && (int) $installment->customer_id !== (int) $value) {
                            $fail('That installment belongs to a different group member, so the payment cannot be attributed to this customer.');
                        }
                    }
                },
            ],
            'amount'               => 'required|numeric|min:0.01',
            'payment_method'       => 'nullable|string|in:cash,bank_transfer,cheque,online',
            'remarks'              => 'nullable|string',
            'paid_at'              => 'nullable|date',
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
