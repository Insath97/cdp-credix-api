<?php

namespace App\Http\Requests;

use App\Models\CustomerBankDetail;
use App\Models\LoanApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateLoanApplicationBankDetailRequest extends FormRequest
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
            'loan_application_id'     => 'required|integer|exists:loan_applications,id',
            'customer_bank_detail_id' => [
                'required',
                'integer',
                'exists:customer_bank_details,id',
                Rule::unique('loan_application_bank_details')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    $bankDetail = CustomerBankDetail::where('id', $value)
                        ->where('customer_id', $loanApplication->customer_id)
                        ->first();

                    if (!$bankDetail) {
                        $fail('This bank detail does not belong to the customer on this loan application.');
                    }
                },
            ],
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
