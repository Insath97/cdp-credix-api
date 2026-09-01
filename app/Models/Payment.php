<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'loan_installment_id',
        'receipt_no',
        'amount',
        'payment_method',
        'received_by',
        'remarks',
        'carry_forward_breakdown',
        'paid_at',
    ];

    protected $casts = [
        'loan_application_id'      => 'integer',
        'loan_installment_id'      => 'integer',
        'amount'                    => 'decimal:2',
        'received_by'               => 'integer',
        'carry_forward_breakdown'   => 'array',
        'paid_at'                   => 'datetime',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with LoanInstallment
     */
    public function loanInstallment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class);
    }

    /**
     * Relationship with the User who received the payment.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
