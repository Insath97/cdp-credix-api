<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlobalSearchRequest extends FormRequest
{
    /**
     * The identification documents a person can be looked up by.
     *
     * These are the three the registration screens offer, and they are stored
     * verbatim on customers, guarantors and employees -- there is no enum
     * behind the column. Listing them here is what stops a typo being answered
     * with a confident "not found": an unknown type is refused rather than
     * searched for.
     */
    public const ID_TYPES = ['NIC', 'Passport', 'Driving License'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_type'   => 'required|string|max:100',
            'id_number' => 'required|string|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $given = mb_strtolower(trim((string) $this->input('id_type')));

            $known = array_map(
                static fn (string $type) => mb_strtolower($type),
                self::ID_TYPES
            );

            
            if ($given !== '' && !in_array($given, $known, true)) {
                $validator->errors()->add(
                    'id_type',
                    'The identification type must be one of: ' . implode(', ', self::ID_TYPES) . '.'
                );
            }
        });
    }
}
