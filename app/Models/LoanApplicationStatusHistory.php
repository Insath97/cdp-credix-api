<?php

namespace App\Models;

use App\Enums\LoanApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * The audit trail of every status a loan application has ever been in.
 *
 * One row per change, written by LoanApplicationStatusHistory::record() and by
 * nothing else. The loan application itself only carries where the file is
 * now; this table is the only place that remembers how it got there — who
 * moved it, when, and why — which is what an audit or a customer dispute
 * actually asks for.
 *
 * Rows are never updated or deleted. A correction is a new row, not an edit,
 * because an audit trail that can be rewritten is not one.
 */
class LoanApplicationStatusHistory extends Model
{
    protected $table = 'loan_application_status_history';

    protected $fillable = [
        'application_id',
        'loan_application_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
        'change_date',
        'changed_at',
        'metadata',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'application_id'      => 'integer',
        'loan_application_id' => 'integer',
        'changed_by'          => 'integer',
        'from_status'         => LoanApplicationStatus::class,
        'to_status'           => LoanApplicationStatus::class,
        'change_date'         => 'date',
        'changed_at'          => 'datetime',
        'metadata'            => 'array',
    ];

    /**
     * Record one status change.
     *
     * The single writer for this table. Every place in the backend that moves
     * a loan application calls this — the workflow service for each
     * transition, and the three creation paths for the opening
     * `null -> submitted` row, so a file's trail starts at its birth instead
     * of at its first review.
     *
     * Static rather than an injected service because it has no dependencies
     * and is called from controllers that would otherwise have to take one
     * just to write an audit row.
     *
     * $from is nullable: on creation there is no previous status, and null is
     * the honest value for that rather than repeating the new one. It is the
     * one column here left nullable, along with the optional metadata bag --
     * every other field is required, and this method guarantees it can always
     * supply one:
     *
     *   - changed_by falls back to the signed-in user, then to the System
     *     account, so an unattended nightly transition still names someone
     *     truthfully.
     *   - remarks falls back to a generated sentence, so no row is ever left
     *     without a reason for the change.
     *
     * The caller is responsible for the surrounding transaction. Where one
     * exists (every workflow transition) the history row commits with the
     * status change it describes, so the two can never disagree.
     */
    public static function record(
        LoanApplication $loanApplication,
        ?LoanApplicationStatus $from,
        LoanApplicationStatus $to,
        ?int $changedBy = null,
        ?string $remarks = null,
        array $metadata = []
    ): self {
        $now = now();

        return static::create([
            'application_id'      => $loanApplication->application_id,
            'loan_application_id' => $loanApplication->id,
            'from_status'         => $from?->value,
            'to_status'           => $to->value,
            'changed_by'          => $changedBy ?? Auth::id() ?? User::systemUserId(),
            'remarks'             => trim((string) $remarks) !== ''
                ? $remarks
                : static::defaultRemarks($from, $to),
            'change_date'         => $now->toDateString(),
            'changed_at'          => $now,
            'metadata'            => $metadata ?: null,
        ]);
    }

    /**
     * The sentence written when a caller supplies no remarks.
     *
     * Plain English rather than the raw enum values, because this is read by
     * whoever opens the file's history, not by code.
     */
    protected static function defaultRemarks(?LoanApplicationStatus $from, LoanApplicationStatus $to): string
    {
        $label = fn (LoanApplicationStatus $status) => Str::title(str_replace('_', ' ', $status->value));

        return $from === null
            ? "Loan application created with status {$label($to)}"
            : "Status changed from {$label($from)} to {$label($to)}";
    }

    /**
     * The generic applications header this entry belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Relationship with the LoanApplication this history entry belongs to.
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with the User who performed the transition.
     *
     * Null for the nightly schedulers — MarkOverdueLoanApplications and the
     * recovery commands move files with no one signed in, and an audit row
     * that named a user for those would be a lie. The remarks say what did it.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
