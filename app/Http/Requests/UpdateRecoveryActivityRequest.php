<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecoveryActivityRequest extends FormRequest
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
            'activity_type'   => 'nullable|string|in:call,visit,payment_promise,collection_attempt',
            'notes'           => 'nullable|string',
            'promised_amount' => 'nullable|numeric|min:0',
            'promised_date'   => 'nullable|date',
            'performed_at'    => 'nullable|date',
        ];
    }

}
