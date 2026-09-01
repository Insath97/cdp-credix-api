<?php

namespace App\Http\Requests;

use App\Enums\LoanApplicationStatus;
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

                    if ($groupLoan && $groupLoan->status !== LoanApplicationStatus::Submitted) {
                        $fail('Items can only be added while the group loan is still in Submitted status.');
                    }
                },
            ],
            'item_name'  => 'required|string|max:255',
            'quantity'   => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
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
