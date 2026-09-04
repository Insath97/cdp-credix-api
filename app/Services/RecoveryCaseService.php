<?php

namespace App\Services;

use App\Models\ExternalRecoveryAgent;
use App\Models\LoanApplication;
use App\Models\RecoveryCase;
use App\Traits\ActivityLogTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Owns the recovery case lifecycle that used to be duplicated across
 * EscalateInternalRecoveryCases, EscalateExternalRecoveryCases and
 * RecoveryCaseController::store(): case numbering, opening, escalating,
 * resolving, and the staff/agent notifications that go with each.
 *
 * Agents are never picked automatically — an admin assigns them through
 * RecoveryCaseController::assignAgent(). What this service does instead is
 * make sure the admin finds out a case is waiting for one.
 */
class RecoveryCaseService
{
    use ActivityLogTrait;

    /**
     * Case statuses that mean "this case is still being worked". Anything not
     * in this list (resolved / escalated / closed) is settled, and no longer
     * blocks a new case from being opened for the same loan.
     */
    public const LIVE_STATUSES = ['open', 'in_progress'];

    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * Stamp the human-readable case number once the row has an id. Callers
     * insert with a UUID placeholder first, because case_no is unique and
     * NOT NULL and the id is not known until after the insert.
     */
    public function applyCaseNo(RecoveryCase $case): RecoveryCase
    {
        $case->case_no = 'RC-' . str_pad($case->id, 6, '0', STR_PAD_LEFT);
        $case->save();

        return $case;
    }

    public function placeholderCaseNo(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Open the first-stage (internal) recovery case for an overdue loan.
     * assigned_agent_id is deliberately left null — the admin assigns it.
     */
    public function openInternalCase(
        LoanApplication $loanApplication,
        float $overdueAmount,
        int $daysOverdue,
        ?int $customerId = null
    ): RecoveryCase {
        $case = RecoveryCase::create([
            'loan_application_id' => $loanApplication->id,
            // Which Group Loan member is being pursued. Null on Individual and
            // Joint loans, where the loan itself is the debtor.
            'customer_id'         => $customerId,
            'case_no'             => $this->placeholderCaseNo(),
            'status'              => 'open',
            'stage'               => 'internal',
            'overdue_amount'      => $overdueAmount,
            'opened_by'           => null,
            'opened_at'           => now(),
            'remarks'             => "Automatically escalated to internal recovery after {$daysOverdue} day(s) overdue.",
        ]);

        $this->applyCaseNo($case);
        $this->notifyStaffOfUnassignedCase($case, $loanApplication, 'internal recovery');

        return $case;
    }

    /**
     * Supersede the internal case and open its external-stage child.
     *
     * The internal case is marked 'escalated', not 'closed': the debt was
     * neither cured nor written off, and 'escalated' is the status the schema
     * has always carried for exactly this. It is outside LIVE_STATUSES either
     * way, so it still stops blocking new cases. closed_at stays null, which
     * keeps RecoveryCaseController::update()'s resolved/closed stamping
     * consistent.
     */
    public function escalateToExternal(
        RecoveryCase $internalCase,
        LoanApplication $loanApplication,
        float $overdueAmount,
        int $daysOverdue
    ): RecoveryCase {
        $internalCase->update([
            'status'  => 'escalated',
            'remarks' => trim(($internalCase->remarks ?? '') . " Escalated to external recovery after {$daysOverdue} day(s) overdue."),
        ]);

        $externalCase = RecoveryCase::create([
            'loan_application_id' => $loanApplication->id,
            // The external case pursues whoever the internal case pursued.
            'customer_id'         => $internalCase->customer_id,
            'case_no'             => $this->placeholderCaseNo(),
            'status'              => 'open',
            'stage'               => 'external',
            'parent_case_id'      => $internalCase->id,
            'overdue_amount'      => $overdueAmount,
            'opened_by'           => null,
            'opened_at'           => now(),
            'remarks'             => "Automatically escalated to external recovery after {$daysOverdue} day(s) overdue.",
        ]);

        $this->applyCaseNo($externalCase);
        $this->notifyStaffOfUnassignedCase($externalCase, $loanApplication, 'external recovery');

        return $externalCase;
    }

    /**
     * Settle every live case on a loan that is no longer in arrears.
     *
     * This is what keeps the "skip loans that already have a live case" guard
     * in both escalation commands safe: without it a stale open case would
     * permanently block a legitimate future one. Idempotent, and sends no
     * notifications, so it is safe to call after every payment.
     */
    public function resolveOpenCases(LoanApplication $loanApplication, string $reason, ?int $customerId = null): int
    {
        $cases = RecoveryCase::where('loan_application_id', $loanApplication->id)
            ->whereIn('status', self::LIVE_STATUSES)
            // Scoped to one Group Loan member when given, so a member catching
            // up settles their own case without touching a sibling who is
            // still behind.
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->get();

        if ($cases->isEmpty()) {
            return 0;
        }

        foreach ($cases as $case) {
            $case->update([
                'status'    => 'resolved',
                'closed_at' => now(),
                'remarks'   => trim(($case->remarks ?? '') . ' ' . $reason),
            ]);
        }

        $this->logActivity('UPDATE', 'RecoveryCase', "Resolved {$cases->count()} recovery case(s) for loan application ID {$loanApplication->id}", [
            'loan_application_id' => $loanApplication->id,
            'case_ids'            => $cases->pluck('id')->all(),
            'reason'              => $reason,
        ]);

        return $cases->count();
    }

    /**
     * Tell the staff role a case has been opened and still needs an agent.
     * Without this an auto-created case would sit unassigned indefinitely,
     * since nothing else surfaces it. Mirrors the staff SMS fan-out in
     * LoanApplicationController::store().
     */
    public function notifyStaffOfUnassignedCase(RecoveryCase $case, LoanApplication $loanApplication, string $stageLabel): void
    {
        $staffRole = Role::where('name', config('notifications.staff_role'))->first();

        if (!$staffRole) {
            return;
        }

        $customerName = $loanApplication->customer?->full_name ?? 'a customer';
        $message = "CDP Credix: {$case->case_no} opened for {$stageLabel} (loan #{$loanApplication->id}, {$customerName}). Please assign an agent.";

        foreach ($staffRole->users as $staffUser) {
            $staffPhone = $staffUser->employee?->phone_primary;

            if (empty($staffPhone)) {
                continue;
            }

            $this->notificationService->sendSms(
                'recovery_case_needs_agent',
                $staffPhone,
                $message,
                ['loan_application_id' => $loanApplication->id, 'user_id' => $staffUser->id]
            );
        }
    }

    /**
     * Notify whichever agent an admin just put on the case. Internal agents
     * are Users (reached through their employee record); external agents are
     * plain contact rows with no account, so they are notified on the phone
     * number stored against them.
     */
    public function notifyAssignedAgent(RecoveryCase $case): void
    {
        $case->loadMissing(['loanApplication.customer', 'assignedAgent.employee', 'externalAgent']);

        $loanApplication = $case->loanApplication;
        $customer = $loanApplication?->customer;

        $summary = sprintf(
            'CDP Credix: Recovery case %s has been assigned to you. Loan #%s, overdue %s.%s',
            $case->case_no,
            $loanApplication?->id ?? '-',
            number_format((float) $case->overdue_amount, 2),
            $customer ? " Customer: {$customer->full_name} {$customer->phone_primary}." : ''
        );

        try {
            if ($case->stage === 'external' && $case->externalAgent && !empty($case->externalAgent->phone)) {
                $this->notificationService->sendSms(
                    'recovery_case_assigned_external_agent',
                    $case->externalAgent->phone,
                    $summary,
                    ['loan_application_id' => $loanApplication?->id]
                );

                return;
            }

            $agentPhone = $case->assignedAgent?->employee?->phone_primary;

            if (!empty($agentPhone)) {
                $this->notificationService->sendSms(
                    'recovery_case_assigned_agent',
                    $agentPhone,
                    $summary,
                    ['loan_application_id' => $loanApplication?->id, 'user_id' => $case->assigned_agent_id]
                );
            }
        } catch (\Throwable $th) {
            // The assignment itself has already been persisted; a failed SMS
            // must not turn a successful assignment into an error response.
            Log::warning('Failed to notify the agent assigned to a recovery case', [
                'recovery_case_id' => $case->id,
                'error'            => $th->getMessage(),
            ]);
        }
    }

    /**
     * The overdue amount a case should carry: the sum of the balances of every
     * installment currently marked overdue.
     *
     * Pass a customer to scope it to one Group Loan member's own arrears, so a
     * member's case carries what that member owes rather than what the whole
     * group owes.
     */
    public function overdueAmountFor(LoanApplication $loanApplication, ?int $customerId = null): float
    {
        $installments = $loanApplication->installments->where('status', 'overdue');

        if ($customerId !== null) {
            $installments = $installments->where('customer_id', $customerId);
        }

        return (float) $installments->sum('balance');
    }

    /**
     * Whether a loan already has a live case, optionally for one specific
     * Group Loan member.
     *
     * This is what keeps per-member arrears working: both escalation commands
     * skip a loan that already has a live case, so without the customer scope
     * one member's open case would permanently prevent a case ever being
     * opened for a different member who falls behind later.
     */
    public function hasLiveCaseFor(LoanApplication $loanApplication, ?int $customerId = null): bool
    {
        return RecoveryCase::where('loan_application_id', $loanApplication->id)
            ->whereIn('status', self::LIVE_STATUSES)
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->exists();
    }
}
