<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Models\RecoveryCase;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EscalateInternalRecoveryCases extends Command
{
    use ActivityLogTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'recovery:escalate-internal';

    /**
     * The console command description.
     */
    protected $description = 'Automatically open an internal recovery case for overdue loan applications that have crossed the configured internal recovery threshold.';

    public function handle(NotificationService $notificationService): int
    {
        if (!Setting::get('recovery_escalation_enabled', true)) {
            $this->info('Recovery escalation is disabled in System Settings. Skipping.');
            return self::SUCCESS;
        }

        $thresholdDays = Setting::get('internal_recovery_threshold_days', 30);

        $loanApplications = LoanApplication::where('status', LoanApplicationStatus::Overdue)
            ->whereDoesntHave('recoveryCases', function ($query) {
                $query->where('stage', 'internal')->whereIn('status', ['open', 'in_progress']);
            })
            ->with(['customer', 'installments'])
            ->get();

        $escalated = 0;

        foreach ($loanApplications as $loanApplication) {
            $earliestOverdueInstallment = $loanApplication->installments
                ->where('status', 'overdue')
                ->sortBy('due_date')
                ->first();

            if (!$earliestOverdueInstallment) {
                continue;
            }

            $daysOverdue = $earliestOverdueInstallment->due_date->diffInDays(now());

            if ($daysOverdue < $thresholdDays) {
                continue;
            }

            $overdueAmount = $loanApplication->installments
                ->where('status', 'overdue')
                ->sum('balance');

            $case = RecoveryCase::create([
                'loan_application_id' => $loanApplication->id,
                'case_no'             => (string) Str::uuid(),
                'status'              => 'open',
                'stage'               => 'internal',
                'overdue_amount'      => $overdueAmount,
                'opened_by'           => null,
                'opened_at'           => now(),
                'remarks'             => "Automatically escalated to internal recovery after {$daysOverdue} day(s) overdue.",
            ]);
            $case->case_no = 'RC-' . str_pad($case->id, 6, '0', STR_PAD_LEFT);
            $case->save();

            if (!empty($loanApplication->customer?->phone_primary)) {
                $notificationService->sendSms(
                    'recovery_case_opened_customer',
                    $loanApplication->customer->phone_primary,
                    "CDP Credix: Your loan account (Loan Application ID: {$loanApplication->id}) has become overdue. Please contact us immediately to avoid further recovery actions.",
                    ['loan_application_id' => $loanApplication->id, 'customer_id' => $loanApplication->customer_id]
                );
            }

            $escalated++;
        }

        $summary = "Loan applications escalated to internal recovery: {$escalated}.";
        $this->info($summary);

        $this->logActivity('CREATE', 'RecoveryCase', $summary, ['escalated' => $escalated]);

        return self::SUCCESS;
    }
}
