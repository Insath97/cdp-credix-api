<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum LoanApplicationStatus: string
{
    /**
     * The single status a customer sees for the whole pre-approval pipeline.
     * Not an enum case: nothing is ever stored as 'processing'.
     */
    public const CUSTOMER_PROCESSING = 'processing';

    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Verified = 'verified';
    case Approved = 'approved';

    /**
     * The customer's answer to the approved offer.
     *
     * Approval is the lender's decision, not the borrower's. Approving less
     * than was asked for is an offer, and the borrower may take it, refuse it,
     * or ask for time -- so the file waits here for their answer instead of
     * going straight to the cash desk.
     */
    case OnHold = 'on_hold';
    case Accepted = 'accepted';
    case Declined = 'declined';

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
            // Three separate hands before money moves: review, then verify,
            // then approve. Each stage records its own actor, and the workflow
            // service refuses to let one person cover two of them.
            self::Submitted => [self::Reviewed, self::Cancelled],
            self::Reviewed => [self::Verified, self::Rejected, self::Cancelled],
            self::Verified => [self::Approved, self::Rejected, self::Cancelled],

            // Money moves only after the borrower has said yes. On Hold is a
            // parking place for "give me a few days", not a decision, so it
            // leads to the same two answers.
            self::Approved => [self::OnHold, self::Accepted, self::Declined],
            self::OnHold => [self::Accepted, self::Declined],
            self::Accepted => [self::Disbursed],
            self::Declined => [self::Cancelled],
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
     * Whether this is an end state: the loan application is finished and can
     * never move again (allowedTransitions() is empty for all three).
     *
     * A row in one of these must never be flagged is_active — that flag is
     * what the listings and the frontend badge read, so a cancelled loan left
     * active shows up as "Active" even though its workflow is over.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Cancelled, self::Rejected, self::Closed], true);
    }

    /**
     * Whether the money has already gone out on this application.
     *
     * Disbursed, and every state that follows it, means the lending decision
     * has been acted on. The credit file's documents are then the record of
     * why it was acted on, so they stop being collectible and become
     * evidence -- see allowsDocumentChanges().
     */
    public function isDisbursedOrLater(): bool
    {
        return in_array($this, [self::Disbursed, self::Active, self::Overdue, self::Closed], true);
    }

    /**
     * Whether documents may still be uploaded, edited or removed.
     *
     * Documents are the evidence the approval rested on. Once the loan is
     * disbursed, letting anyone swap an NIC copy or a salary slip rewrites
     * that evidence after the fact -- the file would no longer show what the
     * approver actually saw. So from Disbursed onwards the document set is
     * frozen, and the upload screen stops offering these applications at all.
     */
    public function allowsDocumentChanges(): bool
    {
        return !$this->isDisbursedOrLater();
    }

    /**
     * Coarse equivalent written through to the generic applications.status
     * column, which is shared with the future lease module and only needs
     * to track broad progress, not the full granular workflow.
     */
    /**
     * The status a customer is allowed to see.
     *
     * Review, verification and approval are the lender's internal
     * maker-checker stages. Which desk a file is sitting on is not the
     * borrower's business, and naming the stage only invites "who is reviewing
     * it, can you push it through" -- so all three collapse to 'processing'.
     *
     * Everything from approval onwards passes through unchanged, Rejected and
     * Cancelled included: those are outcomes the customer has to be told.
     */
    public function customerFacingStatus(): string
    {
        return match ($this) {
            self::Submitted, self::Reviewed, self::Verified => self::CUSTOMER_PROCESSING,
            default => $this->value,
        };
    }

    /**
     * Human-readable form of customerFacingStatus(), for the label the customer
     * portal prints.
     */
    public function customerFacingLabel(): string
    {
        return Str::title(str_replace('_', ' ', $this->customerFacingStatus()));
    }

    public function toApplicationStatus(): string
    {
        return match ($this) {
            self::Submitted, self::Reviewed, self::Verified => 'in_progress',
            // The coarse column tracks broad progress: an offer awaiting the
            // borrower's answer is still an approved file. A declined one is
            // on its way to cancelled and never comes back.
            self::Approved, self::OnHold, self::Accepted => 'approved',
            self::Declined => 'cancelled',
            self::Rejected => 'rejected',
            self::Disbursed, self::Active, self::Overdue => 'disbursed',
            self::Closed => 'closed',
            self::Cancelled => 'cancelled',
        };
    }
}
