<?php

namespace App\Http\Requests;

use App\Enums\GroupLoanStatus;
use App\Models\GroupLoan;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGroupLoanMemberRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_loan_id' => [
                'required',
                'integer',
                'exists:group_loans,id',
                function ($attribute, $value, $fail) {
                    $groupLoan = GroupLoan::find($value);

                    if ($groupLoan && $groupLoan->status !== GroupLoanStatus::Available) {
                        $fail("This group loan is {$groupLoan->status->value} and its members are locked. Members can only be added while the group loan is still Available (before approval).");
                    }
                },
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    $groupLoan = GroupLoan::find($this->input('group_loan_id'));

                    if (!$groupLoan) {
                        return;
                    }

                    $alreadyMember = $groupLoan->memberLoanApplications()
                        ->where('customer_id', $value)
                        ->exists();

                    if ($alreadyMember) {
                        $fail('This customer is already a member of this group loan. Each member must be a different customer.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'group_loan_id' => 'group loan',
            'customer_id'   => 'customer',
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
