<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Exceptions\InvalidLoanApplicationTransitionException;
use App\Exceptions\SecurityLimitExceededException;
use App\Models\Document;
use App\Models\LoanApplication;
use App\Models\LoanApplicationSecurity;
use App\Models\LoanApplicationStatusHistory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LoanApplicationWorkflowService
{
    public function __construct(
        protected InstallmentScheduleService $installmentScheduleService,
        protected ReferenceNumberService $referenceNumberService,
        protected LoanSecurityLtvService $loanSecurityLtv,
        protected LoanCustomerConfirmationService $customerConfirmation,
    ) {
    }

    /**
     * Transition a loan application to a new status, guarded by the enum's
     * allowed-transition map, and record the change in status history.
     *
     * @param array $extra Additional fields to persist alongside the status change
     *                      (e.g. reviewed_by, approved_amount, rejection_reason).
     */
    public function transition(
        LoanApplication $loanApplication,
        LoanApplicationStatus $to,
        ?int $actorId = null,
        ?string $remarks = null,
        array $extra = [],
        array $metadata = []
    ): LoanApplication {
        $from = $loanApplication->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidLoanApplicationTransitionException(
                "Cannot transition loan application from '{$from->value}' to '{$to->value}'."
            );
        }

        $this->assertSegregationOfDuties($loanApplication, $to, $actorId);


        if ($to === LoanApplicationStatus::Verified
            && $loanApplication->group_loan_id === null
            && $loanApplication->loanApplicationGuarantors()->count() === 0) {
            throw new InvalidLoanApplicationTransitionException(
                'At least one guarantor is required before this loan application can be verified.'
            );
        }

        // The customer must have confirmed the details, as they stand now,
        // with OTP 2 before underwriting starts (every co-borrower on a Joint
        // Loan). A change after confirmation makes it stale and blocks again.
        if ($to === LoanApplicationStatus::Reviewed) {
            $this->customerConfirmation->assertConfirmedForReview($loanApplication);
        }

        if (in_array($to, [LoanApplicationStatus::Reviewed, LoanApplicationStatus::Verified], true)) {
            $this->assertSecurityReady($loanApplication, $to === LoanApplicationStatus::Reviewed ? 'reviewed' : 'verified');
        }

        return DB::transaction(function () use ($loanApplication, $from, $to, $actorId, $remarks, $extra, $metadata) {
            // The approval reference, minted the first time this application is
            // approved. Guarded on being null rather than on the transition
            // alone: a loan that is reverted and approved again keeps the
            // reference the customer was already given. Done inside this
            // transaction so its counter lookup is covered by the same lock
            // that ReferenceNumberService takes.
            if ($to === LoanApplicationStatus::Approved && empty($loanApplication->approval_reference_no)) {
                $extra['approval_reference_no'] = $this->referenceNumberService->forApproval($loanApplication);
            }

            $loanApplication->update(array_merge($extra, [
                'status' => $to,
                // A finished loan application must never stay flagged active:
                // is_active is what the listings filter on and what the status
                // badge reads, so a cancelled loan left active still shows as
                // "Active". Applied here rather than in each controller so
                // cancel/reject/close all get it, including the Group Loan
                // cascade, which transitions through this same method.
                // Going the other way has to be said explicitly. Reopening a
                // rejected file passes is_active in $extra, and this line sits
                // second in the array_merge -- so without honouring it here the
                // reopened application inherited the false set at rejection and
                // stayed off every working list. A terminal status still forces
                // false whatever the caller passes; that invariant is not
                // negotiable.
                'is_active' => $to->isTerminal()
                    ? false
                    : ($extra['is_active'] ?? $loanApplication->is_active),
            ]));

            $loanApplication->application()->update([
                'status' => $to->toApplicationStatus(),
            ]);

            LoanApplicationStatusHistory::record(
                $loanApplication,
                $to,
                $actorId,
                $remarks,
                $metadata
            );

            // Generate the installment schedule the moment the loan is
            // approved so the repayment details are already available when
            // the legal document is prepared for signing -- well before the
            // money actually moves at Disbursed.  The guard inside
            // generate() is idempotent (it short-circuits when rows already
            // exist), so a reopen-then-re-approve path is safe.
            if ($to === LoanApplicationStatus::Approved) {
                $this->installmentScheduleService->generate($loanApplication);
            }

            return $loanApplication->fresh([
                'application',
                'customer',
                'loanProduct',
                'branch',
                'appliedByUser',
                'reviewedByUser',
                'verifiedByUser',
                'approvedByUser',
                'assignedReviewer',
                'installments',
            ]);
        });
    }

    /**
     * A secured (Standard Borrowing) loan is not ready for review without its
     * securities, nor without the papers each of them requires
     * (LoanSecurityType::documents()), nor without the securities still
     * covering what the loan is for.
     *
     * All three checks are on the collection. The papers are uploaded
     * separately through POST /documents, so review is the first point at which
     * everything can be looked at together; checked again at verification,
     * because the securities can still be swapped after review and a document
     * can still be removed.
     */
    protected function assertSecurityReady(LoanApplication $loanApplication, string $stage): void
    {
        if (!$loanApplication->requiresSecurity()) {
            return;
        }

        $loanApplication->loadMissing('securities');

        $securities = $loanApplication->securities;

        if ($securities->isEmpty()) {
            throw new InvalidLoanApplicationTransitionException(
                "{$loanApplication->loanProduct->name} is a Standard Borrowing loan, so it must be secured. Add at least one loan security (CDP Investment, Property Mortgage or Vehicle) before this loan application can be {$stage}."
            );
        }

        $this->assertSecuritiesStillCoverTheLoan($loanApplication, $securities, $stage);

        // What papers are still missing, counted across the whole collection.
        // A document records which application it was collected for and not
        // which of that application's securities, so this asks "is a paper of
        // this type on the loan at all" -- binding each document to its own
        // security is not built yet.
        $missing = $securities
            ->flatMap(fn ($security) => collect($security->security_type->documents())
                ->filter()
                ->keys()
                ->map(fn (string $documentType) => [
                    'security_type' => $security->security_type->value,
                    'document_type'  => $documentType,
                ]))
            ->reject(fn (array $needed) => $loanApplication->documents()
                ->ofDocumentType($needed['document_type'])
                ->where('status', 'active')
                ->where('is_active', true)
                ->exists())
            // The same paper satisfies every security of the same type, so two
            // properties missing their deeds are one item to fix, not two.
            ->unique('document_type')
            ->map(fn (array $needed) => Document::TYPES[$needed['document_type']]);

        if ($missing->isNotEmpty()) {
            throw new InvalidLoanApplicationTransitionException(
                'Upload the ' . $missing->join(', the ', ' and the ') . " before this loan application can be {$stage}."
            );
        }
    }

    /**
     * The securities must still carry the loan. Re-checked here, not only on
     * submit, because by review or verification the requested amount, a
     * valuation or a lending plan's percentage may all have moved -- what was
     * written was correct when it was written, not necessarily now.
     *
     * The plan is read from the row and only from the row. Permission is not
     * consulted here and that is deliberate: this is a measurement of a stored
     * loan, not a new pledge, and a reviewer who does not hold the submitter's
     * plan permission must still be able to measure and judge it. A permission
     * withdrawn after submission cannot make the application unreadable.
     *
     * @param Collection<int, LoanApplicationSecurity> $securities
     */
    protected function assertSecuritiesStillCoverTheLoan(
        LoanApplication $loanApplication,
        Collection $securities,
        string $stage
    ): void {
        try {
            $this->loanSecurityLtv->assertWithinLimit(
                (float) $loanApplication->requested_amount,
                $securities->map(fn ($security) => [
                    'security_type' => $security->security_type->value,
                    'security_plan' => $security->security_plan,
                    // The stored value, so a property is measured by its
                    // valuation and a CDP Investment by what Core reported,
                    // whichever columns happen to hold it now.
                    'value'          => $security->value(),
                ])->all()
            );
        } catch (SecurityLimitExceededException $e) {
            throw new InvalidLoanApplicationTransitionException(
                'The loan securities no longer cover this loan, so it cannot be ' . $stage . ". {$e->getMessage()}"
            );
        }
    }

    /**
     * A loan passes through three separate hands before money moves — review,
     * verify, approve — and no one person may cover two of them.
     *
     * The rule is enforced here rather than in the controllers so it applies to
     * Individual, Joint and Group loans alike (the group cascade transitions
     * through this same method). Only the two later stages need checking: the
     * reviewer is by definition the first checker.
     *
     * Super Admin is exempt. Every other role is bound by its permissions plus
     * this rule; Super Admin is the role that exists to be able to act on
     * anything, and the rest of the app already treats it that way — it is the
     * only role that may delete a branch or manage admin users. The exemption
     * means a Super Admin can carry a file from review to approval alone.
     *
     * Turn off for everyone with the `loan_approval_segregation_enabled` System
     * Setting where one officer legitimately handles the whole file — a very
     * small branch — accepting that this removes the maker-checker control.
     */
    protected function assertSegregationOfDuties(
        LoanApplication $loanApplication,
        LoanApplicationStatus $to,
        ?int $actorId
    ): void {
        if ($actorId === null) {
            return;
        }

        if (!Setting::get('loan_approval_segregation_enabled', true)) {
            return;
        }

        if (User::find($actorId)?->isSuperAdmin()) {
            return;
        }

        $conflicts = match ($to) {
            // Reverify is checked alongside Verified because failing a
            // verification is still an act of verification. Without it the
            // reviewer could pick their own file back up and fail it, covering
            // two of the three hands -- the exact thing this rule exists to
            // stop.
            LoanApplicationStatus::Verified,
            LoanApplicationStatus::Reverify => ['reviewed_by' => 'reviewed'],
            LoanApplicationStatus::Approved => ['reviewed_by' => 'reviewed', 'verified_by' => 'verified'],
            default => [],
        };

        foreach ($conflicts as $column => $stage) {
            if ((int) $loanApplication->{$column} !== $actorId) {
                continue;
            }

            $action = in_array($to, [
                LoanApplicationStatus::Verified,
                LoanApplicationStatus::Reverify,
            ], true) ? 'verify' : 'approve';

            throw new InvalidLoanApplicationTransitionException(
                "You already {$stage} this loan application, so you cannot also {$action} it. "
                . 'Review, verification and approval must each be done by a different person.'
            );
        }
    }
}
