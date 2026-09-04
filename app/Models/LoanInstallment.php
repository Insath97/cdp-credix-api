<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'customer_id',
        'loan_revision_id',
        'installment_no',
        'due_date',
        'amount_due',
        'amount_paid',
        'penalty_amount',
        'penalty_waived_by',
        'penalty_waived_reason',
        'balance',
        'status',
        'paid_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'customer_id'          => 'integer',
        'loan_revision_id'    => 'integer',
        'installment_no'      => 'integer',
        'due_date'             => 'date',
        'amount_due'           => 'decimal:2',
        'amount_paid'          => 'decimal:2',
        'penalty_amount'       => 'decimal:2',
        'penalty_waived_by'    => 'integer',
        'balance'               => 'decimal:2',
        'paid_at'               => 'datetime',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * The customer who owes this installment. For a Group Loan this is the
     * member whose own due/paid/balance/penalty/status this row tracks; for an
     * Individual or Joint Loan it is the loan's (primary) customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with the User who waived the penalty.
     */
    public function penaltyWaivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penalty_waived_by');
    }

    /**
     * Relationship with the loan revision that generated this installment, if any.
     */
    public function loanRevision(): BelongsTo
    {
        return $this->belongsTo(LoanRevision::class);
    }

    /**
     * Relationship with the payments applied to this installment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recompute balance from amount_due + penalty_amount - amount_paid, floored
     * at 0. Does not save() -- callers persist alongside their other changes.
     */
    public function recalculateBalance(): void
    {
        $this->balance = max(0, $this->amount_due + $this->penalty_amount - $this->amount_paid);
    }

    /**
     * Whole calendar days between this installment's due date and today, 0 for
     * one that is not yet past due.
     *
     * Carbon 3's diffInDays() returns a float (a due date at midnight compared
     * against a 01:00 scheduler run gives 7.0416...), which used to leak into
     * customer SMS text ("is 7.0416666666667 day(s) overdue") and into
     * recovery case remarks. Snapping both operands to midnight makes the
     * difference whole before the cast, and this is the single place that
     * does it — every threshold comparison and every message reads from here.
     */
    public function daysOverdue(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        return max(0, (int) $this->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay()));
    }

    /**
     * Whether this installment is unpaid and past its due date — the state the
     * grace-period nudge reminders key off, which is deliberately earlier than
     * the formal 'overdue' status (that is only stamped once the product's
     * grace period has fully elapsed).
     */
    public function isPastDue(): bool
    {
        return !in_array($this->status, ['paid', 'waived', 'revised'], true)
            && $this->balance > 0
            && $this->due_date
            && $this->due_date->lt(now()->startOfDay());
    }
}
