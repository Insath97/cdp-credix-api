<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Setting;
use App\Rules\GroupLoanMemberCountMatches;
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
                    $query->where('is_active', true)
                        ->whereIn('loan_type_id', function ($sub) {
                            $sub->select('id')->from('loan_types')->where('code', 'DEVELOPMENT_FUND');
                        });
                }),
            ],
            'branch_id'         => 'nullable|integer|exists:branches,id',
            'group_name'        => 'nullable|string|max:255',
            // One, because a Development Fund loan is submitted through this
            // endpoint for a single borrower as well as for a group -- the
            // scheme is item-based either way, and the wizard sends one member
            // for the individual tier. A floor of two here rejected every
            // individual Development Fund loan outright.
            //
            // The group rule is not weakened by this: member removal refuses to
            // take a loan below GroupLoanWorkflowService::MIN_MEMBERS, so a
            // group that starts with two or more still cannot drop to one, and
            // a single-borrower loan cannot lose its only member either.
            'number_of_members' => 'required|integer|min:1',
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
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',

            'members'               => ['required', 'array', new GroupLoanMemberCountMatches((int) $this->input('number_of_members'))],
            'members.*.customer_id' => 'required|integer|distinct|exists:customers,id',

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
