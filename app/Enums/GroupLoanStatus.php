<?php

namespace App\Enums;

enum GroupLoanStatus: string
{
    /** Applied / under review — still open, nothing committed yet. */
    case Available = 'available';

    /** Approved: amounts are frozen and the loan is queued for disbursement. */
    case Locked = 'locked';

    /** Money released; members are repaying. */
    case Disbursed = 'disbursed';

    /** Every member's loan is fully repaid. */
    case Closed = 'closed';

    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * The four states the frontend exposes as filter tabs. Rejected and
     * Cancelled are real terminal states the reject/cancel endpoints still
     * write, but they are not part of the tab set.
     *
     * @return array<int, self>
     */
    public static function filterable(): array
    {
        return [self::Available, self::Locked, self::Disbursed, self::Closed];
    }

    /**
     * Statuses reachable from the current one via an explicit workflow action.
     * Mirrors LoanApplicationStatus::allowedTransitions(), but at the coarser
     * granularity the group loan header tracks — verification does not move
     * the header (a submitted and a verified group loan are both Available);
     * the granular per-member timeline lives on loan_applications.status.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Available => [self::Locked, self::Rejected, self::Cancelled],
            self::Locked    => [self::Disbursed, self::Cancelled],
            self::Disbursed => [self::Closed],
            default         => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
