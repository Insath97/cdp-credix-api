<?php

namespace App\Traits;

use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use Illuminate\Database\Eloquent\Builder;

/**
 * `used_for_loan` for a customer's own records: fixed and moving assets,
 * liabilities and bank details.
 *
 * These are declared once on the customer, not per loan, so what they back is
 * the owner's loans. The flag is true while the owning customer is on a live
 * loan, as primary borrower or as a joint/group member. "Live" is
 * LoanApplication::live(), the same rule as one-live-loan-per-customer.
 */
trait UsedForCustomerLoan
{
    public function getUsedForLoanAttribute(): bool
    {
        return $this->customer_id !== null
            && LoanApplication::live()->forCustomer((int) $this->customer_id)->exists();
    }

    /**
     * Keep only the records whose owner is (or, with false, is not) on a live loan.
     */
    public function scopeUsedForLoan(Builder $query, bool $used = true): Builder
    {
        $column = $query->qualifyColumn('customer_id');

        $onLiveLoan = function (Builder $q) use ($column) {
            // whereNotNull: a group loan has no primary customer, and a NULL
            // inside NOT IN would make the "not used" filter match nothing.
            $q->whereIn($column, LoanApplication::live()->whereNotNull('customer_id')->select('customer_id'))
              ->orWhereIn($column, LoanApplicationCustomer::query()
                  ->whereHas('loanApplication', fn (Builder $loan) => $loan->live())
                  ->select('customer_id'));
        };

        return $used ? $query->where($onLiveLoan) : $query->whereNot($onLiveLoan);
    }
}
