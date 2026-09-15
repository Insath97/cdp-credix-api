<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'loan_installment_id',
        'customer_id',
        'receipt_no',
        'amount',
        'payment_method',
        'received_by',
        'remarks',
        'carry_forward_breakdown',
        'paid_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id'      => 'integer',
        'loan_installment_id'      => 'integer',
        'customer_id'               => 'integer',
        'amount'                    => 'decimal:2',
        'received_by'               => 'integer',
        'carry_forward_breakdown'   => 'array',
        'paid_at'                   => 'datetime',
    ];

    /**
     * Relationship with LoanApplication
     */
    /**
     * Find a payment the way an officer has one in front of them.
     *
     * They are holding a receipt, or looking at an application number on a
     * file -- not the internal row id, which was the only thing the listing
     * could be filtered by. A bare number still matches the loan application
     * id, so the old behaviour keeps working.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('receipt_no', 'like', "%$search%")
                ->orWhereHas('loanApplication.application', fn ($a) => $a->where('application_no', 'like', "%$search%"))
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('full_name', 'like', "%$search%")
                    ->orWhere('customer_code', 'like', "%$search%"));

            if (ctype_digit(trim($search))) {
                $q->orWhere('loan_application_id', (int) trim($search));
            }
        });
    }

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
     * The Group Loan member who actually handed the money over. Null for
     * Individual/Joint Loans and for group payments recorded without
     * attribution — the installments and outstanding balance stay group-level
     * either way.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with the User who received the payment.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
