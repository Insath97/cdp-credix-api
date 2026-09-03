<?php

namespace App\Http\Requests;

use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecoveryCaseRequest extends FormRequest
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
            'status'            => 'nullable|string|in:open,in_progress,resolved,escalated,closed',
            'stage'             => 'nullable|string|in:internal,external',
            'overdue_amount'    => 'nullable|numeric|min:0',
            'assigned_agent_id' => 'nullable|integer|exists:users,id',
            'external_agent_id' => 'nullable|integer|exists:external_recovery_agents,id',
            'remarks'           => 'nullable|string',
            'closed_at'         => 'nullable|date',
        ];
    }

}
