<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\GuardsLoanWorkflowFields;

class UpdateLoanApplicationRequest extends FormRequest
{
    use GuardsLoanWorkflowFields;

    /**
     * Refused on update but legitimately accepted on create.
     *
     * A loan is born inactive-or-active and at a status, so the create request
     * takes both. Changing either afterwards is a different act with its own
     * endpoint and its own guard, and letting a generic edit do it would walk
     * past that guard -- so they are refused here rather than quietly dropped.
     */
    private const REFUSED_ON_UPDATE = [
        'is_active' => 'the activate, deactivate and toggle-status actions',
        'status'    => 'the workflow actions (review, verify, approve, reject, ...)',
    ];

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
        return array_merge($this->workflowOwnedRules(self::REFUSED_ON_UPDATE), [
            'application_id'         => 'nullable|integer|exists:applications,id',
            'customer_id'            => 'nullable|integer|exists:customers,id',
            'loan_product_id'        => 'nullable|integer|exists:loan_products,id',
            'branch_id'              => 'nullable|integer|exists:branches,id',
            'requested_amount'       => 'nullable|numeric|min:0',
            'interest_rate'          => 'nullable|numeric|min:0|max:999.999',
            'interest_type'          => 'nullable|string|max:255',
            'term_months'            => 'nullable|integer|min:1',
            'monthly_installment'    => 'nullable|numeric|min:0',
            'processing_fee'         => 'nullable|numeric|min:0',
            'monthly_repayment_date' => 'nullable|string|max:255',
            'applied_by'             => 'nullable|integer|exists:users,id',
            'applied_at'             => 'nullable|date',
            'outstanding_balance'    => 'nullable|numeric|min:0',

            // Nullable here, unlike on create: an edit that touches only the
            // amount must not have to resend the recommender, and a rule of
            // 'required' would make every partial update fail.
            'recommended_by_employee_id' => 'nullable|integer|exists:employees,id',
            'recommender_name'           => 'nullable|string|max:255',
            'recommender_employee_code'  => 'nullable|string|max:255',
            'recommender_nic'            => 'nullable|string|max:255',
            'recommender_phone'          => 'nullable|string|max:255',

            // See the note on the create request: an assignment, not a
            // record of something that already happened.
            'assigned_reviewer_id'   => 'nullable|integer|exists:users,id',

            // is_active is deliberately not accepted here: it has its own
            // activate/deactivate/toggle-status endpoints, which refuse to
            // reactivate a cancelled, rejected or closed loan. Allowing it
            // through a generic update would bypass that guard and let a
            // finished loan display as "Active" again.
            //
            // status is not accepted either, for the same reason and a
            // stronger one: every status change belongs to a workflow endpoint
            // that guards the transition, checks segregation of duties and
            // writes the audit row. Setting it here would move a loan with
            // none of that happening.
        ]);
    }


    /**
     * Custom messages for the workflow-owned fields, so a caller that sends
     * one is told which endpoint to use instead of just "is prohibited".
     */
    public function messages(): array
    {
        return $this->workflowOwnedMessages(self::REFUSED_ON_UPDATE);
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
