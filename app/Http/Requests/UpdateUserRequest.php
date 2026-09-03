<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $id = $this->route('user');
        $userObj = \App\Models\User::find($id);
        $employeeId = $userObj?->employee_id ?? 'NULL';

        return [
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            // 'user_type' => 'sometimes|in:admin,staff',
            'user_type' => 'sometimes|in:admin,staff,customer',
            'role' => 'sometimes|string|exists:roles,name',

            'customer_id' => 'sometimes|nullable|exists:customers,id',

            // Staff specific validation (embedded employee details)
            'employee_code' => 'sometimes|string|unique:employees,employee_code,' . $employeeId,
            'id_number' => 'sometimes|string|unique:employees,id_number,' . $employeeId,
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

    public function bodyParameters()
    {
        return [];
    }

}
