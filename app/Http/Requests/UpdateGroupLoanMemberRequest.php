<?php

namespace App\Http\Requests;

use App\Enums\GroupLoanStatus;
use App\Models\LoanApplication;
use App\Traits\FriendlyValidationErrors;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Swap which customer occupies an existing member slot. Editing the
 * customer's own personal details is a different endpoint
 * (UpdateGroupLoanMemberCustomerRequest) because that writes to a record
 * shared with the customer's other loans.
 */
class UpdateGroupLoanMemberRequest extends FormRequest
{
    use FriendlyValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    $member = LoanApplication::find($this->route('id'));

                    if (!$member || !$member->groupLoan) {
                        return;
                    }

                    $groupLoan = $member->groupLoan;

                    if ($groupLoan->status !== GroupLoanStatus::Available) {
                        $fail("This group loan is {$groupLoan->status->value} and its members are locked. Members can only be changed while the group loan is still Available (before approval).");
                        return;
                    }

                    $takenByAnother = $groupLoan->memberLoanApplications()
                        ->where('customer_id', $value)
                        ->where('id', '!=', $member->id)
                        ->exists();

                    if ($takenByAnother) {
                        $fail('This customer is already a member of this group loan. Each member must be a different customer.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return ['customer_id' => 'customer'];
    }
}
