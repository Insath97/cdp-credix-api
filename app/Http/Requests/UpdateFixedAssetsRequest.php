<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFixedAssetsRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'customer_id' => 'required|string|exists:customers,customer_id',
            'owner_name' => 'required|string|max:255',
            'property_location' => 'required|string|max:255',
            'extent' => 'required|string|max:255',
            'market_value' => 'required|string|max:255',
            'is_mortaged' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

}
