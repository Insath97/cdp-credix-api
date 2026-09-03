<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'loan_term_id',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_term_id' => 'integer',
        'is_active'    => 'boolean',
    ];

    /**
     * Scope a query to only include active loan types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search loan types by code, title, or description.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * The loan term this loan type belongs to (via loan_types.loan_term_id).
     */
    public function loanTerm(): BelongsTo
    {
        return $this->belongsTo(LoanTerm::class);
    }

    /**
     * The loan terms associated with this loan type (many-to-many, legacy pivot).
     */
    public function loanTerms(): BelongsToMany
    {
        return $this->belongsToMany(LoanTerm::class);
    }

    /**
     * The loan products classified under this loan type.
     */
    public function loanProducts(): HasMany
    {
        return $this->hasMany(LoanProduct::class);
    }
}
