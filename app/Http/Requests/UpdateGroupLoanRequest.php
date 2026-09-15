<?php

namespace App\Http\Requests;

use App\Models\Setting;
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
            'group_name'     => 'nullable|string|max:255',
            'term_months'    => 'nullable|integer|min:1',

            // The same three fields the create form collects, so the edit form
            // can actually change them -- left out, they were silently dropped
            // by validated() and the edit appeared to do nothing.
            'loan_product_id' => [
                'nullable',
                'integer',
                Rule::exists('loan_products', 'id')->where(function ($query) {
                    $query->where('is_active', true);

                    // is_group_loan marks a product as belonging to the GROUP
                    // tier of the Development Fund scheme. A single-borrower
                    // Development Fund loan is submitted through this same
                    // endpoint and takes an ordinary product of that loan
                    // type, so demanding the flag from it rejected every
                    // individual submission with "The selected loan product id
                    // is invalid."
                    // Exclusive: a group borrower may pick only flagged
                    // products, a single borrower only unflagged ones.
                    $query->where('is_group_loan', $this->borrowerCount() > 1);
                }),
            ],
            'competency' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $allowed = Setting::get('group_loan_competency', []);
                $normalized = array_map(fn ($c) => trim(mb_strtolower($c)), $allowed);
                if (!empty($allowed) && !in_array(trim(mb_strtolower($value)), $normalized, true)) {
                    $fail('The competency must be one of the configured options: ' . implode(', ', $allowed) . '.');
                }
            }],
            // Declared headcount, floored at one to match creation: a
            // Development Fund loan for a single borrower comes through the
            // same endpoint and would otherwise be uneditable. Dropping a
            // group below two members is still refused by the member removal
            // endpoint, which is where that rule belongs.
            'number_of_members' => 'nullable|integer|min:1',

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
