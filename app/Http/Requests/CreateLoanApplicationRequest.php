<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LoanApplicationStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\GuardsLoanWorkflowFields;
use App\Services\CustomerLoanEligibilityService;

class CreateLoanApplicationRequest extends FormRequest
{
    use GuardsLoanWorkflowFields;

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
        return array_merge($this->workflowOwnedRules(), [
            'application_id'         => 'nullable|integer|exists:applications,id',
            'customer_id'            => 'required|integer|exists:customers,id',
            'joint_customer_ids'     => 'nullable|array',
            'joint_customer_ids.*'   => ['integer', 'distinct', 'exists:customers,id', Rule::notIn([$this->input('customer_id')])],
            'loan_product_id'        => 'required|integer|exists:loan_products,id',
            // CDP Core policy pledged as security; required and verified against
            // Core in the controller when the product requires collateral.
            'collateral_policy_number' => 'nullable|string|max:100',
            'branch_id'              => 'nullable|integer|exists:branches,id',
            'requested_amount'       => 'required|numeric|min:0',
            'interest_rate'          => 'required|numeric|min:0|max:999.999',
            'interest_type'          => 'nullable|string|max:255',
            'term_months'            => 'required|integer|min:1',
            'processing_fee'         => 'nullable|numeric|min:0',
            'monthly_repayment_date' => 'nullable|string|max:255',

            'recommended_by_employee_id' => 'nullable|integer|exists:employees,id',
            'recommender_name'           => 'nullable|string|max:255',
            'recommender_employee_code'  => 'nullable|string|max:255',
            'recommender_nic'            => 'nullable|string|max:255',
            'recommender_phone'          => 'nullable|string|max:255',

            'applied_by'             => 'nullable|integer|exists:users,id',
            'applied_at'             => 'nullable|date',
            'outstanding_balance'    => 'nullable|numeric|min:0',
            'is_active'              => 'nullable|boolean',
            'assigned_reviewer_id'   => 'nullable|integer|exists:users,id',
            'status'                 => ['nullable', Rule::enum(LoanApplicationStatus::class)],
            'review_failure_reason' => 'nullable|string|max:1000',
            'review_failed_at' => 'nullable|date',
            'resubmitted_at' => 'nullable|date',
            'resubmission_count' => 'nullable|integer|min:0',

        ]);
    }


    /**
     * Custom messages for the workflow-owned fields, so a caller that sends
     * one is told which endpoint to use instead of just "is prohibited".
     */
    public function messages(): array
    {
        return $this->workflowOwnedMessages();
    }

    /**
     * One live loan per customer -- the primary borrower and every joint
     * co-borrower alike. Only runs on ids that already passed their own rules,
     * so a missing customer is reported once, as "does not exist".
     *
     * The controller repeats this under a row lock inside its transaction;
     * this pass is what gives the officer a field-level message up front.
     */
    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $errors = $validator->errors();

            if (!$errors->has('customer_id') && $this->filled('customer_id')) {
                if ($refusal = CustomerLoanEligibilityService::refusalForCustomer((int) $this->input('customer_id'))) {
                    $this->liveLoanRefusal ??= $refusal;
                    $errors->add('customer_id', $refusal);
                }
            }

            // Investment-backed products are individual loans only.
            if ($this->filled('loan_product_id') && !empty($this->input('joint_customer_ids'))) {
                $product = \App\Models\LoanProduct::find($this->input('loan_product_id'));
                if ($product?->requires_investment_collateral) {
                    $errors->add('joint_customer_ids', 'An investment-backed loan is an individual loan; joint co-borrowers cannot be added.');
                }
            }

            foreach ((array) $this->input('joint_customer_ids', []) as $i => $jointId) {
                $field = "joint_customer_ids.{$i}";
                if ($errors->has($field) || !is_numeric($jointId)) {
                    continue;
                }
                if ($refusal = CustomerLoanEligibilityService::refusalForCustomer((int) $jointId)) {
                    $this->liveLoanRefusal ??= $refusal;
                    $errors->add($field, $refusal);
                }
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
