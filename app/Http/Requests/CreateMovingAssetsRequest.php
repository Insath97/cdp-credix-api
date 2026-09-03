<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class CreateMovingAssetsRequest extends FormRequest
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
            'customer_id' => 'required|string|exists:customers,customer_id',
            'assest_category' => 'required|string|in:vehicle,shares_bonds',
            'owner_name' => 'required|string|max:255',
            'make_model' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'no_of_shares' => 'nullable|integer|min:0',
            'par_value' => 'nullable|numeric|min:0',
            'registation_no' => 'nullable|string|max:255',
            'market_value' => 'nullable|numeric|min:0',
            'mortgage_lease_hire_status' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }

}
