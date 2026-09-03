<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
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
        $id = $this->route('group');
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:groups,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Handle failed validation and return a JSON error response.
     */
}
