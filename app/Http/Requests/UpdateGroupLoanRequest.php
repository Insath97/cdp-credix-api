<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupLoanRequest extends FormRequest
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
            'branch_id'      => 'nullable|integer|exists:branches,id',
            'group_name'     => 'nullable|string|max:255',
            'term_months'    => 'nullable|integer|min:1',
            'is_active'      => 'nullable|boolean',
        ];
    }

}
