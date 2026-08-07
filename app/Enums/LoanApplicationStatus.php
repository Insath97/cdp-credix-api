<?php

namespace App\Enums;

enum LoanApplicationStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Disbursed = 'disbursed';
    case Active = 'active';
    case Overdue = 'overdue';
    case Closed = 'closed';
    case Defaulted = 'defaulted';
    case WrittenOff = 'written_off';
    case Cancelled = 'cancelled';

    /**
     * Statuses reachable from the current one via an explicit workflow action.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Submitted, self::Cancelled],
            self::Submitted => [self::UnderReview, self::Cancelled],
            self::UnderReview => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::Disbursed],
            self::Disbursed => [self::Active],
            self::Active => [self::Closed],
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
            self::Pending => 'pending',
            self::Submitted, self::UnderReview => 'in_progress',
            self::Approved => 'approved',
            self::Rejected => 'rejected',
            self::Disbursed, self::Active, self::Overdue => 'disbursed',
            self::Closed, self::Defaulted, self::WrittenOff => 'closed',
            self::Cancelled => 'cancelled',
        };
    }
}
