<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Confine a listing to the branch the signed-in officer works at.
 *
 * Branch membership lives on the employee record behind the user, not on the
 * user itself, and only staff have one. An admin (or any user with no employee
 * record, such as the development account) is deliberately left unscoped: head
 * office has to be able to see every branch, and a null branch_id must not
 * silently turn into "sees nothing".
 *
 * Applied per listing rather than as a global scope on the models. A global
 * scope would also bite the workflow services, notification fan-out and the
 * scheduled recovery jobs -- none of which run as a branch officer, and all of
 * which legitimately read across branches.
 */
trait ScopesToUserBranch
{
    /**
     * The branch this request is confined to, or null for no confinement.
     */
    protected function userBranchId(): ?int
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user->user_type !== 'staff') {
            return null;
        }

        return $user->employee?->branch_id;
    }

    /**
     * Confine a query whose own table carries branch_id.
     */
    protected function scopeToUserBranch($query, string $column = 'branch_id')
    {
        $branchId = $this->userBranchId();

        return $branchId === null ? $query : $query->where($column, $branchId);
    }

    /**
     * Confine a query whose rows reach a branch through relations.
     *
     * Several tables hang off both a loan application and a customer, and
     * either one answers "whose branch is this". A row is kept when ANY of the
     * named relations resolves to the officer's branch, because the others may
     * simply be null -- a document uploaded at customer registration has no
     * loan application, and one collected for a guarantor has no customer.
     *
     * Rows that reach no branch at all (every relation null) are kept too:
     * they belong to nobody's branch, and hiding them from every officer
     * would make them permanently unreachable.
     *
     * @param array<string, string> $relations relation name => foreign key column
     */
    protected function scopeToUserBranchVia($query, array $relations)
    {
        $branchId = $this->userBranchId();

        if ($branchId === null) {
            return $query;
        }

        return $query->where(function ($outer) use ($relations, $branchId) {
            foreach ($relations as $relation => $foreignKey) {
                $outer->orWhereHas($relation, fn ($q) => $q->where('branch_id', $branchId));
            }

            $outer->orWhere(function ($orphan) use ($relations) {
                foreach ($relations as $foreignKey) {
                    $orphan->whereNull($foreignKey);
                }
            });
        });
    }
}
