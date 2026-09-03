<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Setting;

class UpdateCustomerBankDetailRequest extends FormRequest
{
    use FriendlyValidationErrors;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'    => 'required|string|exists:customers,customer_id',
            'bank_name'      => ['required', 'string', Rule::in(Setting::get('customer_bank_list', []))],
            'branch_name'    => 'nullable|string|max:200',
            'account_number' => 'required|string|max:200',
            'payment_method' => 'required|in:cash,bank_transfer,cheque',
        ];
    }

}
