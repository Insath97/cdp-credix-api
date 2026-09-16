<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use App\Enums\GroupLoanStatus;

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
        'verified_by',
        'approved_by',
        'assigned_reviewer_id',
        'reviewed_remarks',
        'verified_remarks',
        'approval_remarks',
        'rejection_reason',
        'applied_at',
        'reviewed_at',
        'verified_at',
        'approved_at',
        'disbursed_at',
        'status',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
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
        'verified_by'             => 'integer',
        'approved_by'             => 'integer',
        'assigned_reviewer_id'    => 'integer',
        'applied_at'              => 'datetime',
        'reviewed_at'             => 'datetime',
        'verified_at'             => 'datetime',
        'approved_at'             => 'datetime',
        'disbursed_at'            => 'datetime',
        'status'                  => GroupLoanStatus::class,
        'is_active'               => 'boolean',
    ];

    /**
     * Surfaced on every response so the frontend can disable the add/remove/
     * edit member controls without having to re-derive the rule from the
     * status string. The backend enforces the same rule independently.
     */
    protected $appends = ['can_modify_members'];

    public function getCanModifyMembersAttribute(): bool
    {
        return $this->status === GroupLoanStatus::Available;
    }

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
     * The single loan application that carries this whole group's money.
     *
     * A group loan has exactly one — the members are attached to it through
     * loan_application_customers, exactly as Joint Loan co-borrowers are. The
     * installment schedule, outstanding balance, overdue status, penalties and
     * recovery cases all therefore hang off this one row.
     */
    public function loanApplication(): HasOne
    {
        return $this->hasOne(LoanApplication::class, 'group_loan_id');
    }

    /**
     * The group's members, reached through that single loan application's
     * joint-loan pivot.
     */
    public function members(): HasManyThrough
    {
        return $this->hasManyThrough(
            LoanApplicationCustomer::class,
            LoanApplication::class,
            'group_loan_id',       // FK on loan_applications
            'loan_application_id', // FK on loan_application_customers
            'id',
            'id',
        );
    }

    /**
     * Each member's own share of the group's money, derived for display.
     *
     * Nothing here is stored: the group borrows and repays as one loan, so the
     * money lives on the single loan application. The split is even, with the
     * last member absorbing the rounding remainder — the same convention
     * GroupLoanWorkflowService::approve() uses for the header figures.
     */
    public function memberBreakdown(): Collection
    {
        $application = $this->relationLoaded('loanApplication')
            ? $this->loanApplication
            : $this->loanApplication()->first();

        if (!$application) {
            return collect();
        }

        // customerDetail carries the GN and DS divisions the members table
        // displays; without it those columns come back empty.
        $members = $application->loanApplicationCustomers()->with('customer.customerDetail')->get();
        $memberCount = $members->count();

        if ($memberCount === 0) {
            return collect();
        }

        $principal      = (float) ($this->approved_amount ?? $this->requested_amount);
        $serviceCharge  = (float) ($this->service_charge_amount ?? 0);
        $totalRepayment = (float) ($this->total_repayment_amount ?? $principal + $serviceCharge);
        $monthly        = (float) ($application->monthly_installment ?? 0);

        $principalShares = self::splitEvenly($principal, $memberCount);
        $chargeShares    = self::splitEvenly($serviceCharge, $memberCount);
        $totalShares     = self::splitEvenly($totalRepayment, $memberCount);
        $monthlyShares   = self::splitEvenly($monthly, $memberCount);

        $installments = $application->installments()->get();

        return $members->values()->map(function ($member, $index) use (
            $installments,
            $principalShares,
            $chargeShares,
            $totalShares,
            $monthlyShares
        ) {
            $base = [
                'customer_id'           => $member->customer_id,
                'customer'              => $member->customer,
                'member_no'             => $index + 1,
                // Per-group member snapshot — preferred by the review page
                // over the shared customer record when set.
                'member_name'           => $member->member_name,
                'nic'                   => $member->nic,
                'address'               => $member->address,
                'phone_number'          => $member->phone_number,
                'gn_division'           => $member->gn_division,
                'ds_division'           => $member->ds_division,
                'principal_share'       => $principalShares[$index],
                'service_charge_share'  => $chargeShares[$index],
                'total_repayment_share' => $totalShares[$index],
            ];

            $own = $installments->where('customer_id', $member->customer_id);

            // Before disbursement there is no schedule yet, so the member's
            // expected share is all that can be shown.
            if ($own->isEmpty()) {
                return $base + [
                    'monthly_installment' => $monthlyShares[$index],
                    'total_due'           => $totalShares[$index],
                    'paid_amount'         => 0.0,
                    'outstanding_balance' => $totalShares[$index],
                    'penalty_amount'      => 0.0,
                    'installment_status'  => 'not_generated',
                    'is_overdue'          => false,
                    'overdue_amount'      => 0.0,
                    'installments_total'  => 0,
                    'installments_paid'   => 0,
                    'installments_overdue' => 0,
                ];
            }

            $overdue = $own->where('status', 'overdue');
            $paidCount = $own->where('status', 'paid')->count();

            return $base + [
                'monthly_installment'  => round((float) $own->first()->amount_due, 2),
                'total_due'            => round((float) $own->sum('amount_due'), 2),
                'paid_amount'          => round((float) $own->sum('amount_paid'), 2),
                'outstanding_balance'  => round((float) $own->sum('balance'), 2),
                'penalty_amount'       => round((float) $own->sum('penalty_amount'), 2),
                'installment_status'   => $this->memberStatusFor($own, $paidCount),
                'is_overdue'           => $overdue->isNotEmpty(),
                'overdue_amount'       => round((float) $overdue->sum('balance'), 2),
                'installments_total'   => $own->count(),
                'installments_paid'    => $paidCount,
                'installments_overdue' => $overdue->count(),
            ];
        });
    }

    /**
     * A single roll-up status for one member's own run of installments. Overdue
     * wins outright — it is the state the group needs to act on — then fully
     * repaid, then any payment at all.
     */
    private function memberStatusFor(Collection $installments, int $paidCount): string
    {
        if ($installments->where('status', 'overdue')->isNotEmpty()) {
            return 'overdue';
        }

        if ($paidCount === $installments->count()) {
            return 'paid';
        }

        if ($installments->sum('amount_paid') > 0) {
            return 'partially_paid';
        }

        return 'upcoming';
    }

    /**
     * Split an amount into $parts even shares, the last absorbing whatever the
     * rounding left over so the shares always add back up to the total.
     */
    public static function splitEvenly(float $amount, int $parts): array
    {
        if ($parts < 1) {
            return [];
        }

        $share = round($amount / $parts, 2);
        $shares = [];
        $running = 0.0;

        for ($i = 0; $i < $parts; $i++) {
            $value = $i === $parts - 1
                ? round($amount - $running, 2)
                : $share;
            $running += $value;
            $shares[] = $value;
        }

        return $shares;
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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
     * Scope for filtering by lifecycle status, accepting either the enum or
     * its string value (which is what arrives from the frontend filter).
     */
    public function scopeStatus(Builder $query, GroupLoanStatus|string|null $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status', $status instanceof GroupLoanStatus ? $status->value : $status);
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
