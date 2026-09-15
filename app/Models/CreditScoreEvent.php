<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One judged installment in the credit score ledger.
 *
 * Rows here are derived, not authored: CreditScoreService deletes and rebuilds
 * a (loan, customer) pair's events from the installment rows every time it
 * recomputes. Nothing outside that service should write to this table.
 */
class CreditScoreEvent extends Model
{
    use HasFactory;

    /** An installment settled within its due date + credit score grace days. */
    public const TYPE_ON_TIME = 'on_time';

    /** An installment that was eventually paid, but after that window. */
    public const TYPE_LATE_PAID = 'late_paid';

    /** An installment still owing money after that window has elapsed. */
    public const TYPE_LATE_UNPAID = 'late_unpaid';

    /** The two event types that cost the borrower points. */
    public const LATE_TYPES = [self::TYPE_LATE_PAID, self::TYPE_LATE_UNPAID];

    protected $fillable = [
        'customer_id',
        'loan_application_id',
        'loan_installment_id',
        'event_type',
        'points',
        'days_late',
        'occurred_on',
        'remarks',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'customer_id'         => 'integer',
        'loan_application_id' => 'integer',
        'loan_installment_id' => 'integer',
        'points'              => 'decimal:2',
        'days_late'           => 'integer',
        // Serialised as a bare Y-m-d, not the default ISO datetime. A plain
        // 'date' cast renders 2026-09-04 as "2026-09-03T18:30:00Z" -- midnight
        // Asia/Colombo expressed in UTC -- and every date in the frontend is
        // displayed by slicing the first 10 characters, which would show the
        // day before the installment was actually settled.
        'occurred_on'         => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function loanInstallment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class);
    }

    /**
     * Whether this event cost the borrower points.
     */
    public function isLate(): bool
    {
        return in_array($this->event_type, self::LATE_TYPES, true);
    }
}
