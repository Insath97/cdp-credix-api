<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Setting;
use App\Rules\GroupLoanMemberCountMatches;
use App\Services\GroupLoanWorkflowService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGroupLoanRequest extends FormRequest
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

    /**
     * How many borrowers this submission is for.
     *
     * The Development Fund scheme is item-based whether one person takes the
     * loan or twenty, so both tiers come through this endpoint. The member
     * count is the only thing that says which tier it is.
     */
    private function borrowerCount(): int
    {
        $members = $this->input('members');

        if (is_array($members) && $members !== []) {
            return count($members);
        }

        return (int) $this->input('number_of_members', 1);
    }

    public function rules(): array
    {
        return [
            'loan_product_id' => [
                'required',
                'integer',
                Rule::exists('loan_products', 'id')->where(function ($query) {
                    // The Development Fund scheme is item-based whether one
                    // person takes the loan or a whole group, and both tiers
                    // submit through this endpoint. A product is selectable
                    // when it belongs to the Development Fund scheme; the
                    // is_group_loan flag only tells the loan summary which
                    // tier it is, it must not bar an individual borrower from
                    // picking the scheme's own product. Requiring the
                    // Development Fund loan type still keeps Standard
                    // Borrowing products out.
                    // whereNull('deleted_at') because Rule::exists queries the
                    // table directly and never applies the model's SoftDeletes
                    // scope. Deactivating a product stopped it being selectable;
                    // deleting one did not, so a withdrawn product still lent.
                    $query->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->whereIn('loan_type_id', function ($sub) {
                            $sub->select('id')->from('loan_types')->where('code', 'DEVELOPMENT_FUND');
                        });
                }),
            ],
            'branch_id'         => 'nullable|integer|exists:branches,id',
            'group_name'        => 'required|string|max:255',
            // A group is at least two people. Every submission through this
            // endpoint is the group tier, so the floor is unconditional: the
            // declared headcount may never be one.
            //
            // Member removal refuses to take a loan below the same
            // GroupLoanWorkflowService::MIN_MEMBERS, so a group can neither
            // start below two nor be dragged below two afterwards.
            'number_of_members' => 'required|integer|min:' . GroupLoanWorkflowService::MIN_MEMBERS,
            'competency'        => ['required', 'string', function ($attribute, $value, $fail) {
                $allowed = Setting::get('group_loan_competency', []);
                $normalized = array_map(fn ($c) => trim(mb_strtolower($c)), $allowed);
                if (!empty($allowed) && !in_array(trim(mb_strtolower($value)), $normalized, true)) {
                    $fail("The competency must be one of the configured options: " . implode(', ', $allowed) . '.');
                }
            }],
            'term_months' => 'required|integer|min:1',
            'recommended_by_employee_id' => 'nullable|integer|exists:employees,id',
            'recommender_name'           => 'nullable|string|max:255',
            'recommender_employee_code'  => 'nullable|string|max:255',
            'recommender_nic'            => 'nullable|string|max:255',
            'recommender_phone'          => 'nullable|string|max:255',

            // No service_charge_percentage input: a Group Loan's charge always
            // comes from the `group_loan_service_charge_percentage` System
            // Setting, and there is no interest rate at all.

            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',

            // The floor as well as the count-match rule: the two guard
            // different mistakes. Count-match only says the array agrees with
            // the declared headcount, so a one-member submission that also
            // declares one would satisfy it; the floor is what refuses it.
            'members'               => ['required', 'array', 'min:' . GroupLoanWorkflowService::MIN_MEMBERS, new GroupLoanMemberCountMatches((int) $this->input('number_of_members'))],
            // A member is either an existing customer picked from the search (a
            // customer_id arrives) or someone the officer typed straight in — in
            // which case the detail fields below are required and the server
            // creates the customer record from them.
            'members.*.customer_id' => 'nullable|integer|exists:customers,id',
            'members.*.member_name' => 'required|string|max:255',
            'members.*.nic'         => 'required|string|max:100',
            'members.*.address'     => 'required|string|max:500',
            'members.*.phone_number' => 'required|string|max:20',
            'members.*.gn_division' => 'required|string|max:255',
            'members.*.ds_division' => 'required|string|max:255',

            // Guarantors ride along on the same submission instead of a second
            // round of API calls. An individual Development Fund loan carries
            // its two guarantors here; a group tier submits none. The blocks
            // mirror CreateGuarantorRequest so a guarantor proves income the
            // same way whether it is typed here or on the dedicated endpoint.
            'guarantors' => 'sometimes|array|max:2',
            'guarantors.*.full_name' => 'required|string|max:255',
            'guarantors.*.type' => 'required|string|in:guarantor_1,guarantor_2',
            'guarantors.*.id_type' => 'required|string|max:100',
            'guarantors.*.id_number' => 'required|string|max:100',
            'guarantors.*.id_image' => 'nullable|string|max:500',
            'guarantors.*.date_of_birth' => 'nullable|date|before:today',
            'guarantors.*.phone_primary' => 'nullable|string|max:20',
            'guarantors.*.employment_status' => 'required|string|in:Employed,Self-Employed,Unemployed',
            'guarantors.*.occupation' => 'required_if:guarantors.*.employment_status,Employed|nullable|string|max:255',
            'guarantors.*.employer_name' => 'required_if:guarantors.*.employment_status,Employed|nullable|string|max:255',
            'guarantors.*.business_name' => 'required_if:guarantors.*.employment_status,Self-Employed|nullable|string|max:255',
            'guarantors.*.business_registration_number' => 'required_if:guarantors.*.employment_status,Self-Employed|nullable|string|max:255',
            'guarantors.*.business_phone' => 'required_if:guarantors.*.employment_status,Self-Employed|nullable|string|max:20',
            'guarantors.*.date_joined' => 'nullable|date',
            'guarantors.*.salary' => 'required_if:guarantors.*.employment_status,Employed|nullable|numeric|min:0',
            'guarantors.*.allowance' => 'nullable|numeric|min:0',
            'guarantors.*.other_income' => 'nullable|numeric|min:0',
            'guarantors.*.liabilities' => 'nullable|numeric|min:0',
            'guarantors.*.bank_name_of_guarantor' => 'nullable|string|max:255',
            'guarantors.*.bank_account_no_of_guarantor' => 'nullable|string|max:255',
            'guarantors.*.bank_branch_of_guarantor' => 'nullable|string|max:255',
        ];
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $members = $this->input('members');
            if (!is_array($members)) {
                return;
            }

            // Same customer picked for two rows is always a mistake — that is
            // one person standing in twice, and the member count would claim
            // more borrowers than there are. Nulls (typed-in members) are
            // ignored; duplicates only count actual ids.
            $ids = array_values(array_filter(array_map(
                fn ($m) => isset($m['customer_id']) ? (int) $m['customer_id'] : null,
                $members
            )));

            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('members', 'The same customer cannot be added as a member more than once.');
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

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }

}
