<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\LoanApplicationStatus;

class GroupLoan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_loan_no',
        'loan_product_id',
        'branch_id',
        'group_name',
        'number_of_members',
        'competency',
        'requested_amount',
        'approved_amount',
        'service_charge_percentage',
        'term_months',
        'service_charge_amount',
        'total_repayment_amount',
        'amount_per_member',
        'applied_by',
        'reviewed_by',
        'approved_by',
        'assigned_reviewer_id',
        'approval_remarks',
        'rejection_reason',
        'applied_at',
        'reviewed_at',
        'approved_at',
        'disbursed_at',
        'status',
        'is_active',
    ];

    protected $casts = [
        'loan_product_id'        => 'integer',
        'branch_id'               => 'integer',
        'number_of_members'       => 'integer',
        'requested_amount'        => 'decimal:2',
        'approved_amount'         => 'decimal:2',
        'service_charge_percentage' => 'decimal:3',
        'term_months'             => 'integer',
        'service_charge_amount'   => 'decimal:2',
        'total_repayment_amount'  => 'decimal:2',
        'amount_per_member'       => 'decimal:2',
        'applied_by'              => 'integer',
        'reviewed_by'             => 'integer',
        'approved_by'             => 'integer',
        'assigned_reviewer_id'    => 'integer',
        'applied_at'              => 'datetime',
        'reviewed_at'             => 'datetime',
        'approved_at'             => 'datetime',
        'disbursed_at'            => 'datetime',
        'status'                  => LoanApplicationStatus::class,
        'is_active'               => 'boolean',
    ];

    /**
     * Generate a human-readable group_loan_no (e.g. GRP-26090001), mirroring
     * how Application::boot() generates application_no.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $groupLoan) {
            if (empty($groupLoan->group_loan_no)) {
                $prefix = 'GRP-' . date('ym');

                // withTrashed() so a soft-deleted row's number is never
                // reissued to a new group loan (the unique constraint on
                // group_loan_no would otherwise reject the collision).
                $lastGroupLoan = self::withTrashed()
                    ->where('group_loan_no', 'like', $prefix . '%')
                    ->orderBy('group_loan_no', 'desc')
                    ->first();

                $nextSeq = $lastGroupLoan
                    ? intval(substr($lastGroupLoan->group_loan_no, -4)) + 1
                    : 1;

                $groupLoan->group_loan_no = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship with the free-form product/material line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GroupLoanItem::class);
    }

    /**
     * Relationship with each member's own loan application row.
     */
    public function memberLoanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class, 'group_loan_id')->orderBy('group_member_no');
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
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
            $q->where('group_name', 'like', "%$search%")
              ->orWhere('group_loan_no', 'like', "%$search%")
              ->orWhereHas('loanProduct', function ($productQuery) use ($search) {
                  $productQuery->where('name', 'like', "%$search%");
              });
        });
    }
}
