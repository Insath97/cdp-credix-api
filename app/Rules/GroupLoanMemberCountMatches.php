<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures the number of members added to a Group Loan exactly matches the
 * declared number_of_members — rejecting submission if there are too few
 * or too many.
 */
class GroupLoanMemberCountMatches implements ValidationRule
{
    public function __construct(protected int $expectedCount)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $actualCount = is_countable($value) ? count($value) : 0;

        if ($actualCount !== $this->expectedCount) {
            $fail("The number of added members ({$actualCount}) does not match the declared number of members ({$this->expectedCount}).");
        }
    }
}
