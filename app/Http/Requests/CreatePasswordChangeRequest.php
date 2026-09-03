<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class CreatePasswordChangeRequest extends FormRequest
{
    use FriendlyValidationErrors;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'employee_id' => 'nullable|exists:employees,id',
            'otp' => 'nullable|string|max:6',
            'expires_at' => 'nullable|date',
            'status' => 'sometimes|in:pending,approved,rejected,verified',
        ];
    }

}
