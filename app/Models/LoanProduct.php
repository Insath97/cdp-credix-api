<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'loan_type_id',
        'loan_term_id',
        'description',
        'interest_rate',
        'interest_type',
        'min_amount',
        'max_amount',
        'min_term_months',
        'max_term_months',
        'processing_fee_type',
        'processing_fee_value',
        'penalty_value',
        'grace_period_days',
        'is_active',
        'is_islamic',
        'is_group_loan',
    ];

    protected $casts = [
        'loan_type_id'         => 'integer',
        'loan_term_id'         => 'integer',
        'interest_rate'        => 'decimal:3',
        'min_amount'           => 'decimal:2',
        'max_amount'           => 'decimal:2',
        'processing_fee_value' => 'decimal:2',
        'penalty_value'        => 'decimal:2',
        'min_term_months'      => 'integer',
        'max_term_months'      => 'integer',
        'grace_period_days'    => 'integer',
        'is_active'            => 'boolean',
        'is_islamic'           => 'boolean',
        'is_group_loan'        => 'boolean',
    ];

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for Islamic finance products.
     */
    public function scopeIslamic(Builder $query): Builder
    {
        return $query->where('is_islamic', true);
    }

    /**
     * Scope for products designated as usable for a Group Loan.
     */
    public function scopeGroupLoanEligible(Builder $query): Builder
    {
        return $query->where('is_group_loan', true);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    /**
     * Relationship with the loan type classification.
     */
    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    /**
     * Relationship with the loan term classification.
     */
    public function loanTerm(): BelongsTo
    {
        return $this->belongsTo(LoanTerm::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('code', 'like', "%$search%")
              ->orWhere('description', 'like', "%$search%");
        });
    }
}
