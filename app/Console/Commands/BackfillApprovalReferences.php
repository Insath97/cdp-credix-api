<?php

namespace App\Console\Commands;

use App\Enums\LoanApplicationStatus;
use App\Models\LoanApplication;
use App\Services\ReferenceNumberService;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off: mint approval references for loans approved before the reference
 * existed.
 *
 * Without this, every loan already past approval shows a blank where the
 * review screen expects its approval reference. Numbers are issued in approval
 * order so the series reads as a history rather than as whatever order the
 * rows happened to come back in.
 */
class BackfillApprovalReferences extends Command
{
    use ActivityLogTrait;

    protected $signature = 'loans:backfill-approval-references {--dry-run : Show what would be issued without writing}';

    protected $description = 'Issue approval reference numbers to loan applications that were approved before the reference existed.';

    /**
     * Every status a loan can only have reached by being approved first.
     */
    private const APPROVED_ONWARDS = [
        LoanApplicationStatus::Approved,
        LoanApplicationStatus::Disbursed,
        LoanApplicationStatus::Active,
        LoanApplicationStatus::Overdue,
        LoanApplicationStatus::Closed,
    ];

    public function handle(ReferenceNumberService $referenceNumberService): int
    {
        $pending = LoanApplication::whereIn('status', self::APPROVED_ONWARDS)
            ->whereNull('approval_reference_no')
            // Oldest approval first, so reference 000000001 belongs to the
            // first loan this branch ever approved.
            ->orderByRaw('approved_at IS NULL, approved_at ASC')
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Nothing to backfill: every approved loan already has a reference.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $issued = 0;

        // A dry run writes exactly like a real one and then rolls the whole
        // thing back. Skipping the write instead would make every line of the
        // preview read 000000001 -- each loan would ask for "the next number"
        // against a table nothing had been written to -- which is precisely the
        // thing an operator uses a dry run to check.
        if ($dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($pending as $loanApplication) {
                // One transaction per loan so the service's lockForUpdate on
                // the counter actually holds, and so a single bad row cannot
                // undo the references already issued. Nested inside the dry
                // run's outer transaction these become savepoints, which is
                // what makes the single rollback below undo all of them.
                DB::transaction(function () use ($loanApplication, $referenceNumberService, &$issued) {
                    $reference = $referenceNumberService->forApproval($loanApplication);

                    $this->line(sprintf(
                        '  %-8s %-22s %-10s -> %s',
                        "#{$loanApplication->id}",
                        $loanApplication->reference(),
                        $loanApplication->status->value,
                        $reference
                    ));

                    $loanApplication->forceFill(['approval_reference_no' => $reference])->save();
                    $issued++;
                });
            }
        } finally {
            if ($dryRun) {
                DB::rollBack();
            }
        }

        $summary = $dryRun
            ? "Dry run (rolled back): {$issued} loan application(s) would be given an approval reference."
            : "Approval references issued: {$issued}.";

        $this->info($summary);

        if (!$dryRun) {
            $this->logActivity('UPDATE', 'LoanApplication', $summary, ['issued' => $issued]);
        }

        return self::SUCCESS;
    }
}
