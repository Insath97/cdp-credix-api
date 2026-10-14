<?php

namespace App\Http\Requests;

use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Services\CustomerLoanEligibilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateLoanApplicationCustomerRequest extends FormRequest
{
    /**
     * The first one-live-loan refusal raised in withValidator(), if any.
     * failedValidation() shows it as the top-level message, so the officer
     * reads why the loan was refused instead of "There is an issue with the
     * input for customer_id."
     */
    private ?string $liveLoanRefusal = null;

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
            // A member is attached two ways: pass an existing customer_id (a
            // search pick, or a Joint Loan co-borrower), or leave it blank and
            // supply the detail fields below — the server then creates the
            // customer record and links it.
            'customer_id'         => [
                'nullable',
                'integer',
                'exists:customers,id',
                Rule::unique('loan_application_customers')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $loanApplication = LoanApplication::with('groupLoan')->find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    if ((int) $value === (int) $loanApplication->customer_id) {
                        $fail('This customer is already the primary applicant on this loan application.');
                        return;
                    }

                    // A Group Loan's member list follows the group loan's own
                    // lifecycle — open while Available, frozen once approved.
                    // A Joint Loan's co-borrowers follow the application's.
                    $groupLoan = $loanApplication->groupLoan;

                    if ($groupLoan) {
                        if ($groupLoan->status !== GroupLoanStatus::Available) {
                            $fail("This group loan is {$groupLoan->status->value} and its members are locked. Members can only be added while the group loan is still Available (before approval).");
                        }

                        return;
                    }

                    if ($loanApplication->status !== LoanApplicationStatus::Submitted) {
                        $fail('Customers can only be added while the loan application is in Submitted status.');
                    }
                },
            ],
            // Snapshot fields for a member the officer types in without picking
            // an existing customer. Ignored when a customer_id is supplied.
            'member_name'   => 'nullable|string|max:255',
            'nic'           => 'nullable|string|max:100',
            'address'       => 'nullable|string|max:500',
            'phone_number'  => 'nullable|string|max:20',
            'gn_division'   => 'nullable|string|max:255',
            'ds_division'   => 'nullable|string|max:255',
        ];
    }

    /**
     * One live loan per customer: a co-borrower or group member being added
     * must not already be on another live loan. The loan they are being added
     * to is left out of the count. A typed-in member is matched by NIC.
     */
    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $errors = $validator->errors();

            if ($errors->has('loan_application_id') || $errors->has('customer_id')) {
                return;
            }

            $loanApplicationId = (int) $this->input('loan_application_id');

            if ($this->filled('customer_id')) {
                if ($refusal = CustomerLoanEligibilityService::refusalForCustomer((int) $this->input('customer_id'), $loanApplicationId)) {
                    $this->liveLoanRefusal ??= $refusal;
                    $errors->add('customer_id', $refusal);
                }
                return;
            }

            if ($refusal = CustomerLoanEligibilityService::refusalForNic($this->input('nic'), $this->input('member_name'), $loanApplicationId)) {
                $this->liveLoanRefusal ??= $refusal;
                $errors->add('nic', $refusal);
            }
        });
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

        $message = $this->liveLoanRefusal
            ?? ($fieldErrors->count() > 1
                ? 'There are multiple validation errors. Please review the form and correct the issues.'
                : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.');

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }

}
