<?php

namespace App\Http\Requests;

use App\Models\Guarantor;
use App\Models\LoanApplicationGuarantor;
use App\Services\GuarantorLoanLimitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLoanApplicationGuarantorRequest extends FormRequest
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
        $id = $this->route('loan_application_guarantor');

        // The controller takes the route parameter as a plain id, so the link
        // has to be looked up here rather than read off a bound model.
        $link = LoanApplicationGuarantor::find(is_object($id) ? $id->id : $id);

        return [
            'loan_application_id' => 'nullable|integer|exists:loan_applications,id',
            'guarantor_id'        => [
                'nullable',
                'integer',
                'exists:guarantors,id',
                Rule::unique('loan_application_guarantors')->where(function ($query) use ($link) {
                    $loanAppId = $this->loan_application_id ?? $link?->loan_application_id;
                    return $query->where('loan_application_id', $loanAppId);
                })->ignore(is_object($id) ? $id->id : $id),

                // The same ceiling as creating the link. Without it, pointing
                // an existing link at a different guarantor is a way round the
                // limit that never passes through the create request at all.
                function ($attribute, $value, $fail) use ($link) {
                    $loanAppId = $this->loan_application_id ?? $link?->loan_application_id;
                    $guarantor = Guarantor::find($value);

                    if (!$guarantor || $guarantor->id === $link?->guarantor_id) {
                        return;
                    }

                    if ($message = GuarantorLoanLimitService::refusalFor($guarantor, $loanAppId)) {
                        $fail($message);
                    }
                },
            ],
            'guarantor_type'      => 'nullable|string|max:255',
            'status'              => 'nullable|string|in:pending,approved,rejected',
            'remarks'             => 'nullable|string',
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
