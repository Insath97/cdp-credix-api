<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Services\GroupLoanWorkflowService;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateGroupLoanRequest extends FormRequest
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
            'branch_id'      => 'nullable|integer|exists:branches,id',
            'group_name'     => 'sometimes|required|string|max:255',
            'term_months'    => 'nullable|integer|min:1',

            // The same three fields the create form collects, so the edit form
            // can actually change them -- left out, they were silently dropped
            // by validated() and the edit appeared to do nothing.
            'loan_product_id' => [
                'nullable',
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
                    // whereNull('deleted_at') for the same reason as on create:
                    // Rule::exists queries the table directly and never applies
                    // the model's SoftDeletes scope, so a withdrawn product
                    // stayed selectable.
                    $query->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->whereIn('loan_type_id', function ($sub) {
                            $sub->select('id')->from('loan_types')->where('code', 'DEVELOPMENT_FUND');
                        });
                }),
            ],
            'competency' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $allowed = Setting::get('group_loan_competency', []);
                $normalized = array_map(fn ($c) => trim(mb_strtolower($c)), $allowed);
                if (!empty($allowed) && !in_array(trim(mb_strtolower($value)), $normalized, true)) {
                    $fail('The competency must be one of the configured options: ' . implode(', ', $allowed) . '.');
                }
            }],
            // Declared headcount, floored at two to match creation. An edit
            // must not be a way round the floor the create request enforces;
            // member removal refuses the same drop.
            'number_of_members' => 'nullable|integer|min:' . GroupLoanWorkflowService::MIN_MEMBERS,

            // is_active is deliberately not accepted here: it has its own
            // activate/deactivate/toggle-status endpoints, which refuse to
            // reactivate a cancelled, rejected or closed group loan. Allowing
            // it through a generic update would bypass that guard.
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
