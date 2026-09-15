<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Validated against the setting's own declared type. `required` alone
        // let anything through: -1 into max_loans_per_guarantor, which the
        // reader clamps to 0 and 0 means "no limit", so a typo in a number box
        // silently switched the rule off. Same shape of problem for the day
        // counts, the boolean switches and the JSON lists.
        $setting = Setting::where('key', $this->route('key'))->first();

        $rules = ['value' => ['required']];

        switch ($setting?->type) {
            case 'integer':
                // No negative counts, days or point values: every integer
                // setting in the table is a quantity.
                $rules['value'][] = 'integer';
                $rules['value'][] = 'min:0';
                break;

            case 'boolean':
                $rules['value'][] = 'in:0,1,true,false';
                break;

            case 'json':
                $rules['value'][] = function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        return;
                    }

                    $decoded = is_string($value) ? json_decode($value, true) : null;

                    if (!is_array($decoded)) {
                        $fail('This setting must be a list.');
                    }
                };
                break;

            default:
                $rules['value'][] = 'string';
        }

        return $rules;
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

        // The actual reason, not the field name. There is only ever one field
        // here and it is called "value", so "There is an issue with the input
        // for value" told the administrator nothing at all -- while the rule
        // that fired ("must be an integer", "must be at least 0") says exactly
        // what to type instead.
        $message = $errorMessages->first() ?: 'The value is not valid for this setting.';

        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }

}
