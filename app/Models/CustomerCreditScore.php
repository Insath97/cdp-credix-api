<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's repayment score on one loan -- the roll-up of that pair's
 * credit_score_events. See the create migration for why this is per (loan,
 * customer) rather than per loan.
 */
class CustomerCreditScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'loan_application_id',
        'installments_counted',
        'on_time_count',
        'late_count',
        'final_score',
        'computed_at',
        'finalized_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'customer_id'          => 'integer',
        'loan_application_id'  => 'integer',
        'installments_counted' => 'integer',
        'on_time_count'        => 'integer',
        'late_count'           => 'integer',
        'final_score'          => 'decimal:2',
        'computed_at'          => 'datetime',
        'finalized_at'         => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * The individual judged installments behind this score.
     *
     * Deliberately a query builder and not an Eloquent relation. The ledger is
     * keyed on (loan, customer), and a relation carrying a `where` on
     * $this->customer_id is an eager-loading trap: `with('events')` builds the
     * constraint from the first model in the collection and then applies that
     * one customer's filter to every row, so a group loan's members would all
     * be handed the first member's ledger. Being a plain method, this can only
     * ever be called on the instance it belongs to.
     */
    public function events(): Builder
    {
        return CreditScoreEvent::query()
            ->where('loan_application_id', $this->loan_application_id)
            ->where('customer_id', $this->customer_id)
            ->orderBy('id');
    }

    /**
     * Scores for loans that have been repaid in full -- the only ones that are
     * a finished verdict rather than a running tally.
     */
    public function scopeFinalized(Builder $query): Builder
    {
        return $query->whereNotNull('finalized_at');
    }
}
