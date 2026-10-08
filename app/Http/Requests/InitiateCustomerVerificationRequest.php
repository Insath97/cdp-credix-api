<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCustomerVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // If called from /customer-verifications/initiate, customer_id is in body
            // If called from /customers/{customerId}/verification/initiate, route param is used
            'customer_id' => 'sometimes|required|integer|exists:customers,id',
        ];
    }
}
