<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Defaults to the request's own field and proposed value when left out.
            'target'  => 'nullable|string|max:100',
            'value'   => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
}
