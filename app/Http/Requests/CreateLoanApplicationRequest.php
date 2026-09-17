<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LoanApplicationStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\GuardsLoanWorkflowFields;

class CreateLoanApplicationRequest extends FormRequest
{
    use GuardsLoanWorkflowFields;

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
