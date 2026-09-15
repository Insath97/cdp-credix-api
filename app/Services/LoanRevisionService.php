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

            switch ($revisionType) {
                case LoanRevisionType::PrincipalOnly->value:
                    // Only the capital is still owed: the profit, and a group
                    // loan's service charge, are written off. Neither is stored
                    // per installment -- an installment carries one blended
                    // amount_due -- so the capital still outstanding is the
                    // outstanding balance scaled by principal's share of the
                    // whole schedule.
                    //
                    // The processing fee is not part of this: it is withheld
                    // from the disbursement (net_disbursement_amount) and never
                    // enters the schedule, so there is nothing left to waive.
                    $liveTotalPayable = (float) $unpaidLive->sum('amount_due')
                        + (float) $loanApplication->installments()
                            ->where('status', 'paid')
                            ->sum('amount_due');

                    if ($liveTotalPayable <= 0) {
                        throw new \InvalidArgumentException('This loan has no schedule to work out the principal share from.');
                    }

                    $principalShare = (float) $loanApplication->approved_amount / $liveTotalPayable;

                    // Waiving the profit is not a rescheduling: the borrower
                    // keeps the months they already have and each one simply
                    // costs less. So the term defaults to what is still left
                    // to run, and is only overridden if a caller states one.
                    $statedTerm = (int) ($data['revised_term'] ?? 0);
                    $revisedTerm = $statedTerm > 0 ? $statedTerm : $previousTerm;

                    $revisedOutstandingAmount = round($previousOutstandingAmount * $principalShare, 2);
                    $revisedInstallmentAmount = round($revisedOutstandingAmount / $revisedTerm, 2);
                    break;

                case LoanRevisionType::ReduceInstallment->value:
                    // The officer names the monthly figure the borrower can
                    // manage and the term stretches to cover the same debt.
                    // Rounded up, so the last month absorbs the remainder
                    // rather than the borrower owing more than they agreed to
                    // pay in any single month.
                    $revisedInstallmentAmount = round((float) ($data['revised_installment_amount'] ?? 0), 2);

                    if ($revisedInstallmentAmount <= 0) {
                        throw new \InvalidArgumentException('A revised monthly instalment amount is required.');
                    }

                    if ($revisedInstallmentAmount >= (float) $previousInstallmentAmount) {
                        throw new \InvalidArgumentException('The revised monthly instalment must be lower than the current one, which is ' . number_format((float) $previousInstallmentAmount, 2) . '.');
                    }

                    $revisedOutstandingAmount = $previousOutstandingAmount;
                    $revisedTerm = (int) ceil($revisedOutstandingAmount / $revisedInstallmentAmount);
                    break;

                case LoanRevisionType::ExtendTerm->value:
                    // The officer names the new term and the monthly figure
                    // falls out of it. The debt itself does not change.
                    $revisedTerm = (int) ($data['revised_term'] ?? 0);

                    if ($revisedTerm <= $previousTerm) {
                        throw new \InvalidArgumentException('The revised term must be greater than the current remaining term of ' . $previousTerm . ' month(s).');
                    }

                    $revisedOutstandingAmount = $previousOutstandingAmount;
                    $revisedInstallmentAmount = round($revisedOutstandingAmount / $revisedTerm, 2);
                    break;

                default:
                    throw new \InvalidArgumentException("Unhandled revision type '{$revisionType}'.");
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
            // The revised schedule is now the whole of the debt, so the
            // running counter has to follow it. It only mattered once
            // principal_only started writing off the profit: reduce_installment
            // and extend_term leave the amount owed alone, so a stale counter
            // happened to still be right, while a waiver left the loan
            // reporting more outstanding than its own schedule asks for.
            'outstanding_balance' => $revision->revised_outstanding_amount,
        ]);
    }
}
