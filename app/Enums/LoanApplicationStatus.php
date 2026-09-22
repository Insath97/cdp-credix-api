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

    /**
     * Review looked at the file and could not pass it on.
     *
     * Not the same as Rejected. Rejection is a lending decision and is final;
     * this is "what you sent us is not good enough to check" -- an unreadable
     * NIC scan, a missing salary slip, a pay slip for the wrong month. The
     * customer is told by SMS, fixes the documents and resubmits, and the file
     * goes back to Submitted for review to look at again. So it is deliberately
     * NOT terminal.
     */
    case ReviewFailed = 'review_failed';
    case Verified = 'verified';

    /**
     * Verification could not pass the file on, so it is waiting to be
     * verified again.
     *
     * The stage after review splits two ways: verification either passes the
     * file to approval, or it fails and the file lands here. Named for what
     * has to happen next rather than for what went wrong -- a file sitting in
     * Reverify is a file on the verification desk's queue for a second look,
     * which is what an officer scanning the list needs to know.
     *
     * The customer is told why by SMS. Like ReviewFailed this is deliberately
     * not terminal and not a rejection: rejection is the lender's answer to
     * the request, this is a checking step that has not finished yet.
     */
    case Reverify = 'reverify';
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

    /**
     * A rejected file that an authorised officer has put back into play.
     *
     * Deliberately its own status rather than a silent return to Submitted:
     * "this was rejected and someone overturned it" is exactly the thing an
     * audit asks about, and collapsing it into Submitted would leave the
     * status column unable to tell a reopened file from a fresh one. The
     * rejection itself is untouched -- it stays in loan_application_status_history
     * as the row it always was, and the reopen is a new row after it.
     */
    case Reopened = 'reopened';
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
            self::Submitted => [self::Reviewed, self::ReviewFailed, self::Cancelled],

            // A failed review is a round trip, not an end: the customer
            // resubmits the documents and the file returns to Submitted for
            // review to look at again. Rejected stays available for the case
            // where review already knows the answer is no.
            self::ReviewFailed => [self::Submitted, self::Rejected, self::Cancelled],
            // Review splits two ways. Passing verification sends the file
            // to approval; failing it drops the file into Reverify rather than
            // ending it, because "we could not confirm this" is not the same
            // answer as "no".
            self::Reviewed => [self::Verified, self::Reverify, self::Rejected, self::Cancelled],

            self::Verified => [self::Approved, self::Rejected, self::Cancelled],

            // A second look, with the same three ways out that Reviewed has.
            // It cannot go back to Reviewed: review already happened and its
            // result still stands -- what failed was verification, and
            // verification is what runs again.
            self::Reverify => [self::Verified, self::Rejected, self::Cancelled],

            // Money moves only after the borrower has said yes. On Hold is a
            // parking place for "give me a few days", not a decision, so it
            // leads to the same two answers.
            // The single way out of a rejection, and only by the explicit
            // reopen action -- nothing else in the workflow leads here.
            self::Rejected => [self::Reopened],

            // A reopened file re-enters the workflow exactly where a new one
            // does. It is not sent straight back to Rejected: reversing a
            // rejection and then re-rejecting without a fresh review would
            // leave no record of anyone having looked at it again.
            self::Reopened => [self::Reviewed, self::ReviewFailed, self::Cancelled],

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
     * Whether this is an end state: the loan application's workflow is over
     * and it must not be flagged is_active. That flag is what the listings and
     * the frontend badge read, so a cancelled loan left active shows up as
     * "Active" even though there is nothing left to do with it.
     *
     * Cancelled and Closed can never move again. Rejected has exactly one way
     * out -- the reopen action, which is a deliberate, permissioned reversal
     * rather than a step in the workflow -- so it is still an end state for
     * every purpose that reads this: it is not live, it cannot be reactivated
     * through toggle-status, and it is not editable. Reopening is what brings
     * it back, and Reopened is not terminal.
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
            // Reopened sits here too. The customer was told the file was
            // rejected; leaving them on that after it has been put back into
            // play would be wrong, and the internal fact that it was reopened
            // is no more their business than which desk it is on.
            self::Submitted, self::Reviewed, self::Verified, self::Reopened => self::CUSTOMER_PROCESSING,
            // The two internal stages the customer must see. For
            // ReviewFailed nothing moves until they reupload, so hiding it
            // behind 'processing' would leave them waiting for a file that is
            // waiting for them. For Reverify they have just been sent an SMS
            // saying verification failed, and a portal still reading
            // 'Processing' next to that message is worse than telling them
            // nothing.
            self::ReviewFailed => self::ReviewFailed->value,
            self::Reverify => self::Reverify->value,
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
            self::Submitted, self::Reviewed, self::Verified,
            self::ReviewFailed, self::Reverify, self::Reopened => 'in_progress',
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
