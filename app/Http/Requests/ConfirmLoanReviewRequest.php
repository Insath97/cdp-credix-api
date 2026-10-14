<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmLoanReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp'                  => 'required|string|digits:6',
            'declaration_accepted' => 'required|accepted',
        ];
    }
}
