<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Shared FormRequest failure response.
 *
 * The `message` a FormRequest returns is what the frontend puts in front of a
 * user, so it carries the actual rule message ("The competency must be one of
 * the configured options: ...") rather than naming the field that failed and
 * leaving the user to guess what about it was wrong. The per-field `errors`
 * array is unchanged, so forms can still highlight individual inputs.
 */
trait FriendlyValidationErrors
{
    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();

        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field'    => $field,
                'messages' => $messages,
            ];
        })->values();

        $allMessages = $errorMessages->all();
        $remaining = count($allMessages) - 1;

        $message = $remaining > 0
            ? $allMessages[0] . ' (' . $remaining . ' more ' . ($remaining === 1 ? 'issue' : 'issues') . ' to fix.)'
            : ($allMessages[0] ?? 'The submitted data could not be accepted.');

        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $fieldErrors,
        ], 422));
    }
}
