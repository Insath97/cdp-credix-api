<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendCustomerOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_token' => 'required|string',
        ];
    }
}
