<?php

namespace App\Enums;

enum LoanApplicationStatus: string
{

    case Submitted = 'submitted';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Disbursed = 'disbursed';
    case Active = 'active';
    case Overdue = 'overdue';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /**
     * Statuses reachable from the current one via an explicit workflow action.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Verified, self::Cancelled],
            self::Verified => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::Disbursed],
            self::Disbursed => [self::Active],
            self::Active => [self::Overdue, self::Closed],
            self::Overdue => [self::Active, self::Closed],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Coarse equivalent written through to the generic applications.status
     * column, which is shared with the future lease module and only needs
     * to track broad progress, not the full granular workflow.
     */
    public function toApplicationStatus(): string
    {
        return match ($this) {
            self::Submitted, self::Verified => 'in_progress',
            self::Approved => 'approved',
            self::Rejected => 'rejected',
            self::Disbursed, self::Active, self::Overdue => 'disbursed',
            self::Closed => 'closed',
            self::Cancelled => 'cancelled',
        };
    }
}
