<?php

namespace App\Enums;

enum LoanRevisionStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Statuses reachable from the current one via an explicit workflow action.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingApproval => [self::Approved, self::Rejected, self::Cancelled],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Statuses that block new payments on the parent loan application.
     *
     * @return array<int, self>
     */
    public static function unresolved(): array
    {
        return [self::PendingApproval];
    }
}
