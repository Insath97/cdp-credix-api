<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage'          => 'required|string|max:100',
            'field_name'     => 'required|string|max:100',
            'current_value'  => 'nullable|string|max:500',
            'proposed_value' => 'required|string|max:500',
            'reason'         => 'required|string|min:3|max:1000',
        ];
    }
}
