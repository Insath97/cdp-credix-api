<?php

namespace App\Services;

use App\Models\Guarantor;

/**
 * The ceiling on how many live loans one person may stand guarantor for.
 *
 * The limit is a branch policy, so it lives in system settings as
 * `max_loans_per_guarantor` rather than in the code, and 0 turns it off.
 *
 * Two requests attach guarantors to applications -- create and update -- and
 * the refusal has to read the same on both, so the wording lives here rather
 * than being written out twice and drifting apart.
 */
class GuarantorLoanLimitService
{
    /**
     * Why this guarantor may not be added, or null if they may.
     *
     * @param int|null $loanApplicationId The application they are being added
     *        to. It is left out of the count so that re-saving a link the
     *        person already holds does not read as one loan too many.
     */
    public static function refusalFor(Guarantor $guarantor, $loanApplicationId = null): ?string
    {
        $limit = Guarantor::maxLoansPerGuarantor();

        if ($limit <= 0) {
            return null;
        }

        $count = $guarantor->liveLoanCount($loanApplicationId ? (int) $loanApplicationId : null);

        if ($count < $limit) {
            return null;
        }

        // Name the person, not the row: the officer is looking at a guarantor
        // they just typed in and has no idea it is the same human being as a
        // record filed under another customer. The ID number is what makes
        // that connection checkable.
        $who = $guarantor->full_name ?: 'This guarantor';
        $identifier = trim((string) $guarantor->id_number) !== ''
            ? " ({$guarantor->id_type} {$guarantor->id_number})"
            : '';

        return sprintf(
            '%s%s is already guaranteeing %d live %s. The system allows %d per guarantor, so they cannot be added to another until one of those loans is closed, rejected or cancelled.',
            $who,
            $identifier,
            $count,
            $count === 1 ? 'loan' : 'loans',
            $limit
        );
    }
}
