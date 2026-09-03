<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanTermRequest extends FormRequest
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
        $id = $this->route('loan_term');

        return [
            'code' => 'sometimes|string|max:50|unique:loan_terms,code,' . $id,
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'loan_type_ids' => 'sometimes|array',
            'loan_type_ids.*' => 'integer|exists:loan_types,id',
        ];
    }

    /**
     * Handle failed validation and return a JSON error response.
     */
}
