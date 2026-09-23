<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CdpCustomerVerificationRequest extends FormRequest
{
    /**
     * The identification types CDP Connect accepts, in the form it expects
     * them -- lower case and underscored.
     *
     * Not the same spelling as Credix's own GlobalSearchRequest::ID_TYPES,
     * which holds 'NIC', 'Passport', 'Driving License' because that is what is
     * stored on customers.id_type. Anything wiring the two together has to
     * translate; CdpConnectService lower-cases what it is given, which covers
     * NIC -> nic but not 'Driving License' -> driving_license.
     */
    public const ID_TYPES = ['nic', 'passport', 'driving_license', 'other'];

    /** Investment states CDP Connect can filter on. */
    public const STATUSES = ['all', 'approved', 'expired', 'pending'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_number' => 'required|string|max:100',
            'id_type'   => 'nullable|string|in:' . implode(',', self::ID_TYPES),
            'status'    => 'nullable|string|in:' . implode(',', self::STATUSES),
        ];
    }
}
