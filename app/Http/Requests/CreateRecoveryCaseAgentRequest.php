<?php

namespace App\Http\Requests;

use App\Models\RecoveryCase;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class CreateRecoveryCaseAgentRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The case being assigned. Null when the id is unknown — the controller
     * then returns its own 404, so the rules simply stay permissive.
     */
    protected function recoveryCase(): ?RecoveryCase
    {
        return RecoveryCase::find($this->route('id'));
    }

    public function rules(): array
    {
        $stage = $this->recoveryCase()?->stage;

        return [
            'assigned_agent_id' => [
                Rule::requiredIf(fn () => $stage === 'internal'),
                'nullable',
                'integer',

                Rule::exists('recovery_agents', 'user_id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
                function ($attribute, $value, $fail) use ($stage) {
                    if ($stage === 'external' && !empty($value)) {
                        $fail('This case is at the external stage, so it is worked by an external recovery agency — set external_agent_id instead.');
                    }
                },
            ],
            'external_agent_id' => [
                Rule::requiredIf(fn () => $stage === 'external'),
                'nullable',
                'integer',
                Rule::exists('external_recovery_agents', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
                function ($attribute, $value, $fail) use ($stage) {
                    if ($stage === 'internal' && !empty($value)) {
                        $fail('This case is at the internal stage, so it is worked by your own recovery officer — set assigned_agent_id instead.');
                    }
                },
            ],
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_agent_id.required' => 'An internal recovery case needs one of your own recovery officers assigned to it.',
            'assigned_agent_id.exists'   => 'That user is not an active recovery agent.',
            'external_agent_id.required' => 'An external recovery case needs an external recovery agency assigned to it.',
            'external_agent_id.exists'   => 'That external recovery agency does not exist or is inactive.',
        ];
    }

    public function attributes(): array
    {
        return [
            'assigned_agent_id' => 'recovery officer',
            'external_agent_id' => 'external recovery agency',
        ];
    }

      protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();
        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field' => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }
}
