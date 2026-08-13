<?php

namespace App\Http\Requests;

use App\Models\MovingAssests;
use App\Models\LoanApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateLoanApplicationMovingAssetRequest extends FormRequest
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
            'loan_application_id' => 'required|integer|exists:loan_applications,id',
            'moving_assest_id'    => [
                'required',
                'integer',
                'exists:moving_assests,id',
                Rule::unique('loan_application_moving_assets')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
                function ($attribute, $value, $fail) {
                    $loanApplication = LoanApplication::find($this->loan_application_id);

                    if (!$loanApplication) {
                        return;
                    }

                    $movingAsset = MovingAssests::where('id', $value)
                        ->where('customer_id', $loanApplication->customer_id)
                        ->first();

                    if (!$movingAsset) {
                        $fail('This asset does not belong to the customer on this loan application.');
                        return;
                    }

                    if ($movingAsset->used_for_loan) {
                        $fail('This asset is already pledged to an active loan application.');
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
