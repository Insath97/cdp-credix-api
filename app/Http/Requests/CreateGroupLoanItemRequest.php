<?php

namespace App\Http\Requests;

use App\Enums\GroupLoanStatus;
use App\Models\GroupLoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGroupLoanItemRequest extends FormRequest
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
            'group_loan_id' => [
                'required',
                'integer',
                'exists:group_loans,id',
                function ($attribute, $value, $fail) {
                    $groupLoan = GroupLoan::find($value);

                    if ($groupLoan && $groupLoan->status !== GroupLoanStatus::Available) {
                        $fail("This group loan is {$groupLoan->status->value} and can no longer be changed. Items can only be added while it is still Available (before approval).");
                    }
                },
            ],
            'item_name'  => 'required|string|max:255',
            'quantity'   => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
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


