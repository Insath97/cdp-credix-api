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

    protected $casts = [
        'loan_application_id' => 'integer',
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
}
