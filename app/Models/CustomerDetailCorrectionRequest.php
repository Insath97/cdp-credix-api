<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A correction the customer asked for from their loan review link. Submitting
 * one changes nothing; staff review it and, on approval, the change is written
 * to the target record. The row itself is the audit trail: the value replaced,
 * the value written, any knock-on changes, and who did it when.
 */
class CustomerDetailCorrectionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public const STAGE_1 = 'stage_1';
    public const STAGE_2 = 'stage_2';
    public const STAGE_3 = 'stage_3';

    protected $fillable = [
        'loan_application_id',
        'customer_id',
        'loan_customer_confirmation_id',
        'review_stage',
        'field_name',
        'current_value',
        'proposed_value',
        'reason',
        'status',
        'applied_target',
        'old_value',
        'applied_value',
        'applied_changes',
        'review_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'current_value'   => 'encrypted',
        'proposed_value'  => 'encrypted',
        'old_value'       => 'encrypted',
        'applied_value'   => 'encrypted',
        'applied_changes' => 'encrypted:array',
        'reviewed_at'     => 'datetime',
    ];

    /**
     * Requests still waiting on staff. While any are open for a customer,
     * that customer cannot confirm the loan and no new link is sent.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_REVIEW], true);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The review link the request was submitted from.
     */
    public function confirmation(): BelongsTo
    {
        return $this->belongsTo(LoanCustomerConfirmation::class, 'loan_customer_confirmation_id');
    }

    /**
     * The staff member who rejected or applied it.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
