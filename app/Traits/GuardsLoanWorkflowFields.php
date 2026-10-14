<?php

namespace App\Traits;

/**
 * Refuse loan application columns that only the workflow may write.
 *
 * Most of loan_applications is not form data. The stage stamps, the failure
 * reasons, the offer response, the approved figures and the approval reference
 * are all written by the endpoint that performs the corresponding action —
 * review, review-fail, resubmit, verify, verify-fail, reverify, approve,
 * reject, the offer routes, disburse — each of which has its own permission,
 * its own status guard, its own segregation-of-duties check and its own audit
 * row in loan_application_status_history.
 *
 * Accepting any of them on a plain create or update would let a caller walk
 * straight past all of that: stamp themselves as the approver without the
 * maker-checker rule firing, write a rejection reason on a loan nobody
 * rejected, reset resubmission_count to hide a file on its fourth attempt, or
 * clear verify_failed_at so a failed verification reads as though it never
 * happened. None of those would appear in the status history either, because
 * no transition took place.
 *
 * Left out of rules() entirely they would be silently dropped by validated(),
 * which is safe but confusing — the caller sends a value, gets a 200, and the
 * value is nowhere. `prohibited` makes the refusal explicit and says which
 * endpoint owns the field instead.
 *
 * This is the same reasoning UpdateLoanApplicationRequest already applies to
 * is_active, collected here so the create and update rules cannot drift apart.
 */
trait GuardsLoanWorkflowFields
{
    /**
     * field => the phrase naming what owns it, used to build both the rules
     * and the messages so the two can never fall out of step.
     *
     * @return array<string, string>
     */
    protected function workflowOwnedFields(): array
    {
        return [
            // The three hands before money moves.
            'reviewed_by'           => 'the review action',
            'reviewed_at'           => 'the review action',
            'reviewed_remarks'      => 'the review action',
            'verified_by'           => 'the verify action',
            'verified_at'           => 'the verify action',
            'verified_remarks'      => 'the verify action',
            'approved_by'           => 'the approve action',
            'approved_at'           => 'the approve action',
            'approval_remarks'      => 'the approve action',

            // A failed review, and the customer's resubmission that answers it.
            'review_failure_reason' => 'the review-fail action',
            'review_failed_at'      => 'the review-fail action',
            'resubmitted_at'        => 'the resubmit action',
            'resubmission_count'    => 'the resubmit action',

            // A failed verification, and the second look that answers it.
            'verify_failure_reason' => 'the verify-fail action',
            'verify_failed_at'      => 'the verify-fail action',
            'reverify_count'        => 'the reverify action',

            'rejection_reason'      => 'the reject action',
            'disbursed_at'          => 'the disburse action',

            // The borrower's answer to the approved offer.
            'offer_responded_by'    => 'the hold-offer, accept-offer and decline-offer actions',
            'offer_responded_at'    => 'the hold-offer, accept-offer and decline-offer actions',
            'offer_decline_reason'  => 'the decline-offer action',
            'offer_remarks'         => 'the hold-offer, accept-offer and decline-offer actions',

            // Derived on approval from the approved principal, never sent in.
            'approved_amount'         => 'the approve action',
            'net_disbursement_amount' => 'the approve action',

            // Minted by ReferenceNumberService on the first approval and never
            // regenerated: a reference the customer already has must survive
            // the loan being reverted and approved again.
            'approval_reference_no'   => 'the approve action',

            // A group loan's application is created by the group loan endpoint,
            // which snapshots the service charge and splits the money across
            // the members. Attaching one here would produce a group loan whose
            // figures were never calculated.
            'group_loan_id'           => 'the group loan endpoints',
        ];
    }

    /**
     * @param array<string, string> $extra Further field => owner pairs this
     *                                     particular request refuses.
     * @return array<string, string>
     */
    protected function workflowOwnedRules(array $extra = []): array
    {
        return array_map(fn () => 'prohibited', array_merge($this->workflowOwnedFields(), $extra));
    }

    /**
     * @return array<string, string>
     */
    protected function workflowOwnedMessages(array $extra = []): array
    {
        $messages = [];

        foreach (array_merge($this->workflowOwnedFields(), $extra) as $field => $owner) {
            $messages["{$field}.prohibited"] =
                "The {$field} field cannot be set here. It is written by {$owner}.";
        }

        return $messages;
    }
}
