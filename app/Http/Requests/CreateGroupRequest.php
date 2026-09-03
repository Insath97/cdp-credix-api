<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:groups,code|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Handle failed validation and return a JSON error response.
     */
}
