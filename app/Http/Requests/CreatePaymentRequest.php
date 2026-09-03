<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
    use FriendlyValidationErrors;

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
            'loan_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('loan_installments', 'id')->where(function ($query) {
                    return $query->where('loan_application_id', $this->loan_application_id);
                }),
            ],
            'amount'               => 'required|numeric|min:0.01',
            'payment_method'       => 'nullable|string|in:cash,bank_transfer,cheque,online',
            'remarks'              => 'nullable|string',
            'paid_at'              => 'nullable|date',
        ];
    }

}
