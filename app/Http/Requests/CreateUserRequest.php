<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required_if:user_type,admin|nullable|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required_if:user_type,admin|nullable|string|min:8',
            // 'user_type' => 'required|in:admin,staff',
            'user_type' => 'required|in:admin,staff,customer',

            'role' => 'required|string|exists:roles,name',

            'customer_id' => 'required_if:user_type,customer|nullable|exists:customers,id',

            // Staff specific validation (embedded employee details)
            'employee_code' => 'required_if:user_type,staff|nullable|string|unique:employees,employee_code',
            'id_number' => 'required_if:user_type,staff|nullable|string|unique:employees,id_number',
            'phone' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'zonal_id' => 'nullable|exists:zonals,id',
            'region_id' => 'nullable|exists:regions,id',
            'province_id' => 'nullable|exists:provinces,id',
            'designation_id' => 'nullable|exists:designations,id',
            'reporting_manager_id' => 'nullable|exists:employees,id',

            'is_active' => 'sometimes|boolean',
            'can_login' => 'sometimes|boolean',
        ];
    }

}
