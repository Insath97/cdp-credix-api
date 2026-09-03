<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('branch');
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20|unique:branches,code,' . $id,
            'group_id' => 'nullable|exists:groups,id',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'zone_id' => 'sometimes|exists:zonals,id',
            'region_id' => 'sometimes|exists:regions,id',
            'province_id' => 'sometimes|exists:provinces,id',
            'phone_primary' => 'sometimes|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'fax' => 'nullable|string|max:20',
            'opening_date' => 'sometimes|date',
            'branch_type' => 'sometimes|in:main,city,satellite,mobile',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
            'is_head_office' => 'sometimes|boolean',
        ];
    }

}
