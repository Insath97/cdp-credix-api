<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Setting;
use App\Rules\GroupLoanMemberCountMatches;

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
    public function rules(): array
    {
        return [
            'loan_product_id' => [
                'required',
                'integer',
                Rule::exists('loan_products', 'id')->where(function ($query) {
                    $query->where('is_group_loan', true)->where('is_active', true);
                }),
            ],
            'branch_id'         => 'nullable|integer|exists:branches,id',
            'group_name'        => 'required|string|max:255',
            'number_of_members' => 'required|integer|min:2',
            'competency'        => ['required', 'string', function ($attribute, $value, $fail) {
                $allowed = Setting::get('group_loan_competency', []);
                $normalized = array_map(fn ($c) => trim(mb_strtolower($c)), $allowed);
                if (!empty($allowed) && !in_array(trim(mb_strtolower($value)), $normalized, true)) {
                    $fail("The competency must be one of the configured options: " . implode(', ', $allowed) . '.');
                }
            }],
            'term_months' => 'required|integer|min:1',

            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',

            'members'               => ['required', 'array', new GroupLoanMemberCountMatches((int) $this->input('number_of_members'))],
            'members.*.customer_id' => 'required|integer|exists:customers,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();

        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field'    => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors'  => $fieldErrors,
        ], 422));
    }
}
