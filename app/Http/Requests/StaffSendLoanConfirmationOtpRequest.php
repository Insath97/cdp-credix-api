<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffSendLoanConfirmationOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Why the customer's OTP has to be entered by staff, kept on the confirmation.
            'reason' => 'required|string|min:10|max:1000',
        ];
    }
}
