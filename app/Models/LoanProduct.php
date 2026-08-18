<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
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
    ];

    protected $casts = [
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

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
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
