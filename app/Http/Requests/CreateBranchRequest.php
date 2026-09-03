<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class CreateBranchRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:branches,code|max:20',
            'group_id' => 'nullable|exists:groups,id',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'zone_id' => 'required|exists:zonals,id',
            'region_id' => 'required|exists:regions,id',
            'province_id' => 'required|exists:provinces,id',
            'phone_primary' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'fax' => 'nullable|string|max:20',
            'opening_date' => 'required|date',
            'branch_type' => 'required|in:main,city,satellite,mobile',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
            'is_head_office' => 'sometimes|boolean',
        ];
    }

}
