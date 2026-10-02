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

    /**
     * Which loans are secured -- must carry a loan security (a mortgage):
     * the Standard Borrowing loans under the General loan term, Individual
     * and Joint alike. Matched by code, the same way group loans are tied to
     * DEVELOPMENT_FUND, so there is no per-product flag to keep in step.
     */
    public const SECURED_LOAN_TYPE_CODE = 'STANDARD_BORROWING';
    public const SECURED_LOAN_TERM_CODE = 'GENERAL';

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

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * requires_security is derived from the loan type, not stored; it is
     * appended so the wizard can still read it off the product.
     */
    protected $appends = [
        'requires_security',
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

    /**
     * Whether loans on this product must carry a loan security: its loan type
     * is Standard Borrowing under the General term. The product's term is
     * always its type's (LoanProductController derives one from the other).
     */
    public function requiresSecurity(): bool
    {
        // Through the relations when they are already loaded; otherwise asked
        // without loading them, so serialising a product does not drag its
        // type and term into the JSON as a side effect.
        if ($this->relationLoaded('loanType') && $this->relationLoaded('loanTerm')) {
            return $this->loanType?->code === self::SECURED_LOAN_TYPE_CODE
                && $this->loanTerm?->code === self::SECURED_LOAN_TERM_CODE;
        }

        return self::isSecuredLoanType($this->loan_type_id);
    }

    /**
     * The same rule for a loan type that has no product yet -- what the
     * product create and update requests need.
     */
    public static function isSecuredLoanType(int|string|null $loanTypeId): bool
    {
        if (empty($loanTypeId)) {
            return false;
        }

        $type = LoanType::with('loanTerm')->find($loanTypeId);

        return $type?->code === self::SECURED_LOAN_TYPE_CODE
            && $type?->loanTerm?->code === self::SECURED_LOAN_TERM_CODE;
    }

    /**
     * requires_security as the product JSON carries it. Null when the product
     * was loaded without its type and term (a narrow select such as
     * 'loanProduct:id,name'), where the honest answer is "unknown".
     */
    public function getRequiresSecurityAttribute(): ?bool
    {
        if (!array_key_exists('loan_type_id', $this->attributes) || !array_key_exists('loan_term_id', $this->attributes)) {
            return null;
        }

        return $this->requiresSecurity();
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
