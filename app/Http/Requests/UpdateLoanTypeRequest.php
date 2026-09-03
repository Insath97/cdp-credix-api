<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanTypeRequest extends FormRequest
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
        $id = $this->route('loan_type');

        return [
            'code' => 'sometimes|string|max:50|unique:loan_types,code,' . $id,
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'loan_term_id' => 'sometimes|integer|exists:loan_terms,id',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Handle failed validation and return a JSON error response.
     */
}
