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

    /**
     * Normalise a boolean setting's value before anything else looks at it.
     *
     * Turning a switch off was impossible in two independent ways, and either
     * one alone was enough to break it.
     *
     * A real JSON `false` never passed `in:0,1,true,false`, because that rule
     * compares `(string) $value` against the list: `true` casts to "1" and is
     * found, but `false` casts to "" and is not. So `{"value": true}` was
     * accepted and `{"value": false}` was rejected as invalid.
     *
     * The string `"false"` did pass, and was then stored verbatim, because
     * Setting::serialize() had no branch for it. Setting::get() casts a boolean
     * setting with `(bool)`, and in PHP `(bool) "false"` is true -- so the API
     * answered 200 and the feature stayed on.
     *
     * Coercing here, where the setting's declared type is known, fixes both:
     * the rule below sees a genuine bool, and serialize() writes "1" or "0".
     */
    protected function prepareForValidation(): void
    {
        $setting = Setting::where('key', $this->route('key'))->first();

        if ($setting?->type !== 'boolean' || ! $this->has('value')) {
            return;
        }

        $coerced = filter_var($this->input('value'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        // Left alone when it is not boolean-ish at all, so the rule below still
        // reports it as invalid rather than quietly reading it as false.
        if ($coerced !== null) {
            $this->merge(['value' => $coerced]);
        }
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

        // A key that does not exist is the controller's 404 to give, not a
        // validation failure. Without this the switch fell through to its
        // `string` default, so PUT /settings/no_such_key answered 422 "The
        // value field must be a string" -- which says nothing about the only
        // thing actually wrong with the request.
        if (! $setting) {
            return $rules;
        }

        switch ($setting->type) {
            case 'integer':
                // No negative counts, days or point values: every integer
                // setting in the table is a quantity.
                $rules['value'][] = 'integer';
                $rules['value'][] = 'min:0';
                break;

            case 'boolean':
                // `boolean`, not `in:0,1,true,false`. prepareForValidation()
                // has already turned every spelling the frontend might send
                // into a genuine bool, and this rule accepts one; the old `in`
                // rule could not, because it compared the value as a string.
                $rules['value'][] = 'boolean';
                break;

            case 'decimal':
                // A percentage that is multiplied into what a group actually
                // repays. It was declared `string`, which the switch had no
                // case for, so it fell to the `string` default and accepted
                // anything: 'ten percent' casts to 0.0 and silently waives the
                // charge on every group loan approved afterwards, and a
                // negative one makes the group owe less than it borrowed.
                $rules['value'][] = 'numeric';
                $rules['value'][] = 'min:0';
                $rules['value'][] = 'max:100';
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
