<?php

namespace App\Services;

use App\Enums\LoanApplicationStatus;
use App\Enums\LoanRevisionStatus;
use App\Enums\LoanRevisionType;
use App\Exceptions\InvalidLoanRevisionTransitionException;
use App\Models\LoanApplication;
use App\Models\LoanRevision;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanRevisionService
{
    public function __construct(protected InstallmentScheduleService $installmentScheduleService)
    {
    }

    /**
     * Create a new loan revision, submitted directly for approval.
     *
     * @param array $data Expects: revision_type, revised_term (nullable), reason
     */
    public function create(LoanApplication $loanApplication, array $data, int $actorId): LoanRevision
    {
        return DB::transaction(function () use ($loanApplication, $data, $actorId) {
            $loanApplication = LoanApplication::lockForUpdate()->find($loanApplication->id);

            if (!$loanApplication->loanProduct?->is_islamic) {
                throw new \InvalidArgumentException('Loan revisions are only available for Islamic finance loan products.');
            }

            if (!Setting::get('loan_revision_enabled', true)) {
                throw new \InvalidArgumentException('Loan revisions are currently disabled.');
            }

            $allowedTypes = Setting::get('loan_revision_allowed_types', [
                LoanRevisionType::ReduceInstallment->value,
                LoanRevisionType::ExtendTerm->value,
                LoanRevisionType::PrincipalOnly->value,
            ]);
            if (!in_array($data['revision_type'], $allowedTypes, true)) {
                throw new \InvalidArgumentException("Revision type '{$data['revision_type']}' is not currently allowed.");
            }

            if (!in_array($loanApplication->status, [LoanApplicationStatus::Active, LoanApplicationStatus::Overdue], true)) {
                throw new \InvalidArgumentException('Only active or overdue loan applications can be revised.');
            }

            $hasApprovedRevision = $loanApplication->revisions()
                ->where('status', LoanRevisionStatus::Approved)
                ->exists();
            if ($hasApprovedRevision) {
                throw new \InvalidArgumentException('This loan application has already been revised once and cannot be revised again.');
            }

            if ($loanApplication->hasUnresolvedRevision()) {
                throw new \InvalidArgumentException('This loan application already has a revision pending approval.');
            }

            $unpaidLive = $loanApplication->installments()
                ->where('status', '!=', 'revised')
                ->whereNotIn('status', ['paid'])
                ->orderBy('installment_no')
                ->get();

            $previousTerm = $unpaidLive->count();
            if ($previousTerm < 1) {
                throw new \InvalidArgumentException('There are no remaining unpaid installments to revise.');
            }

            $previousInstallmentAmount = $unpaidLive->first()->amount_due;
            $sumUnpaidBalances = $unpaidLive->sum('balance');

            if ($loanApplication->outstanding_balance === null) {
                // Pre-existing loans disbursed before outstanding_balance was auto-initialized
                // on disbursement won't have it set — fall back to the live schedule and backfill.
                $previousOutstandingAmount = $sumUnpaidBalances;
                $loanApplication->update(['outstanding_balance' => $previousOutstandingAmount]);
            } else {
                $previousOutstandingAmount = $loanApplication->outstanding_balance;
                if (abs($sumUnpaidBalances - $previousOutstandingAmount) > 0.01) {
                    Log::warning('Loan revision: outstanding_balance drifted from sum of unpaid installment balances.', [
                        'loan_application_id' => $loanApplication->id,
                        'outstanding_balance' => $previousOutstandingAmount,
                        'sum_unpaid_balances' => $sumUnpaidBalances,
                    ]);
                }
            }

            $revisionType = $data['revision_type'];

            if ($revisionType === LoanRevisionType::PrincipalOnly->value) {
                $revisedOutstandingAmount = $previousOutstandingAmount;
                $revisedTerm = 1;
                $revisedInstallmentAmount = $revisedOutstandingAmount;
            } else {
                $revisedTerm = (int) ($data['revised_term'] ?? 0);
                if ($revisedTerm <= $previousTerm) {
                    throw new \InvalidArgumentException('The revised term must be greater than the current remaining term.');
                }

                $revisedOutstandingAmount = $previousOutstandingAmount;
                $revisedInstallmentAmount = round($revisedOutstandingAmount / $revisedTerm, 2);
            }

            $nextRevisionNo = (int) $loanApplication->revisions()->max('revision_no') + 1;

            return LoanRevision::create([
                'loan_application_id'         => $loanApplication->id,
                'revision_no'                 => $nextRevisionNo,
                'revision_type'               => $revisionType,
                'status'                      => LoanRevisionStatus::PendingApproval,
                'previous_outstanding_amount' => $previousOutstandingAmount,
                'previous_term'               => $previousTerm,
                'previous_installment_amount' => $previousInstallmentAmount,
                'revised_outstanding_amount'  => $revisedOutstandingAmount,
                'revised_term'                => $revisedTerm,
                'revised_installment_amount'  => $revisedInstallmentAmount,
                'reason'                      => $data['reason'],
                'document'                    => $data['document'] ?? null,
                'requested_by'                => $actorId,
            ]);
        });
    }

    /**
     * Transition a loan revision to a new status, guarded by the enum's
     * allowed-transition map. Approval regenerates the installment schedule.
     */
    public function transition(
        LoanRevision $revision,
        LoanRevisionStatus $to,
        int $actorId,
        ?string $remarks = null
    ): LoanRevision {
        $from = $revision->status;

        if (!$from->canTransitionTo($to)) {
            throw new InvalidLoanRevisionTransitionException(
                "Cannot transition loan revision from '{$from->value}' to '{$to->value}'."
            );
        }

        return DB::transaction(function () use ($revision, $to, $remarks, $actorId) {
            $revision = LoanRevision::lockForUpdate()->find($revision->id);

            $extra = ['status' => $to];
            if ($remarks !== null) {
                $extra['remarks'] = $remarks;
            }

            if ($to === LoanRevisionStatus::Approved) {
                $extra['approved_by'] = $actorId;
                $extra['approved_at'] = now();
                $extra['effective_date'] = now()->toDateString();
            }

            $revision->update($extra);

            if ($to === LoanRevisionStatus::Approved) {
                $this->applyApprovedRevision($revision);
            }

            return $revision->fresh(['loanApplication', 'requester', 'approver', 'installments']);
        });
    }

    /**
     * Mark the current live unpaid schedule as revised and generate the new one.
     */
    protected function applyApprovedRevision(LoanRevision $revision): void
    {
        $loanApplication = $revision->loanApplication()->lockForUpdate()->first();

        $loanApplication->installments()
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'revised')
            ->update(['status' => 'revised']);

        $this->installmentScheduleService->generateFromRevision($loanApplication, $revision);

        $loanApplication->update([
            'term_months'         => $revision->revised_term,
            'monthly_installment' => $revision->revised_installment_amount,
        ]);
    }
}
