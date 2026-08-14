<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\LoanApplicationStatus;

class LoanApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'customer_id',
        'loan_product_id',
        'branch_id',
        'requested_amount',
        'approved_amount',
        'interest_rate',
        'interest_type',
        'term_months',
        'monthly_installment',
        'processing_fee',
        'monthly_repayment_date',
        'applied_by',
        'reviewed_by',
        'approved_by',
        'approval_remarks',
        'rejection_reason',
        'applied_at',
        'reviewed_at',
        'approved_at',
        'disbursed_at',
        'outstanding_balance',
        'is_active',
        'status',
        'assigned_reviewer_id',
        'current_approval_level',
    ];

    protected $casts = [
        'application_id'      => 'integer',
        'customer_id'         => 'integer',
        'loan_product_id'     => 'integer',
        'branch_id'           => 'integer',
        'requested_amount'    => 'decimal:2',
        'approved_amount'     => 'decimal:2',
        'interest_rate'        => 'decimal:3',
        'term_months'         => 'integer',
        'monthly_installment' => 'decimal:2',
        'processing_fee'      => 'decimal:2',
        'applied_by'          => 'integer',
        'reviewed_by'         => 'integer',
        'approved_by'         => 'integer',
        'applied_at'          => 'datetime',
        'reviewed_at'         => 'datetime',
        'approved_at'         => 'datetime',
        'disbursed_at'        => 'datetime',
        'outstanding_balance' => 'decimal:2',
        'is_active'            => 'boolean',
        'status'                => LoanApplicationStatus::class,
        'assigned_reviewer_id'  => 'integer',
        'current_approval_level' => 'integer',
    ];

    /**
     * Relationship with the parent Application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Relationship with Customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with LoanProduct.
     */
    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    /**
     * Relationship with Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship with the User who applied.
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Relationship with the User who reviewed.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Relationship with the User who approved.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship with LoanApplicationGuarantors.
     */
    public function loanApplicationGuarantors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanApplicationGuarantor::class);
    }

    /**
     * Relationship with pledged fixed assets (via the pivot).
     */
    public function loanApplicationFixedAssets(): HasMany
    {
        return $this->hasMany(LoanApplicationFixedAsset::class);
    }

    /**
     * Relationship with pledged moving assets (via the pivot).
     */
    public function loanApplicationMovingAssets(): HasMany
    {
        return $this->hasMany(LoanApplicationMovingAsset::class);
    }

    /**
     * Relationship with linked liabilities (via the pivot).
     */
    public function loanApplicationLiabilities(): HasMany
    {
        return $this->hasMany(LoanApplicationLiability::class);
    }

    /**
     * Relationship with linked bank details (via the pivot).
     */
    public function loanApplicationBankDetails(): HasMany
    {
        return $this->hasMany(LoanApplicationBankDetail::class);
    }

    /**
     * Relationship with the assigned reviewer.
     */
    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    /**
     * Relationship with the workflow status change history.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(LoanApplicationStatusHistory::class)->orderBy('created_at');
    }

    /**
     * Relationship with the installment schedule.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('installment_no');
    }

    /**
     * Relationship with the payment history.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    /**
     * Relationship with the recovery cases.
     */
    public function recoveryCases(): HasMany
    {
        return $this->hasMany(RecoveryCase::class);
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for searching.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->whereHas('customer', function ($customerQuery) use ($search) {
                $customerQuery->where('full_name', 'like', "%$search%")
                              ->orWhere('customer_code', 'like', "%$search%");
            })
            ->orWhereHas('application', function ($appQuery) use ($search) {
                $appQuery->where('application_no', 'like', "%$search%");
            })
            ->orWhereHas('loanProduct', function ($productQuery) use ($search) {
                $productQuery->where('name', 'like', "%$search%");
            });
        });
    }
}
