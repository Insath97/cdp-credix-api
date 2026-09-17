<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanRevisionStatus;

class LoanApplication extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Why a borrower turned down an approved offer.
     *
     * A fixed list rather than free text: the useful question is not what one
     * customer wrote, it is how many walked away because the approved amount
     * was too small -- and that can only be counted if everyone picks from the
     * same set. 'other' carries the long tail in offer_remarks.
     */
    public const OFFER_DECLINE_REASONS = [
        'amount_too_low'          => 'Approved amount too low',
        'interest_rate_too_high'  => 'Interest rate too high',
        'term_not_suitable'       => 'Repayment term not suitable',
        'no_longer_needed'        => 'No longer needs the loan',
        'borrowed_elsewhere'      => 'Borrowed from elsewhere',
        'other'                   => 'Other',
    ];

    protected $fillable = [
        'application_id',
        'customer_id',
        'loan_product_id',
        'branch_id',
        'group_loan_id',
        'requested_amount',
        'approved_amount',
        'interest_rate',
        'interest_type',
        'term_months',
        'monthly_installment',
        'processing_fee',
        'net_disbursement_amount',
        'monthly_repayment_date',
        'applied_by',
        'reviewed_by',
        'verified_by',
        'approved_by',
        'reviewed_remarks',
        'verified_remarks',
        'approval_remarks',
        'rejection_reason',
        // Set when review sends the file back to the customer for better
        // documents, cleared when they resubmit.
        'review_failure_reason',
        'review_failed_at',
        'resubmitted_at',
        'resubmission_count',
        'applied_at',
        'reviewed_at',
        'verified_at',
        'approved_at',
        'disbursed_at',
        // The borrower's answer to the approved offer.
        'offer_responded_by',
        'offer_responded_at',
        'offer_decline_reason',
        'offer_remarks',
        'outstanding_balance',
        // Written only by LoanApplicationWorkflowService on the first approval.
        'approval_reference_no',
        // The recommending employee: the link, plus a snapshot of what they
        // asserted on the day. See the create migration for why both.
        'recommended_by_employee_id',
        'recommender_name',
        'recommender_employee_code',
        'recommender_nic',
        'recommender_phone',
        'is_active',
        'status',
        'assigned_reviewer_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'application_id'      => 'integer',
        'customer_id'         => 'integer',
        'loan_product_id'     => 'integer',
        'branch_id'           => 'integer',
        'group_loan_id'       => 'integer',
        'requested_amount'    => 'decimal:2',
        'approved_amount'     => 'decimal:2',
        'interest_rate'        => 'decimal:3',
        'term_months'         => 'integer',
        'monthly_installment' => 'decimal:2',
        'processing_fee'      => 'decimal:2',
        'net_disbursement_amount' => 'decimal:2',
        'applied_by'          => 'integer',
        'reviewed_by'         => 'integer',
        'verified_by'         => 'integer',
        'approved_by'         => 'integer',
        'review_failed_at'    => 'datetime',
        'resubmitted_at'      => 'datetime',
        'resubmission_count'  => 'integer',
        'applied_at'          => 'datetime',
        'reviewed_at'         => 'datetime',
        'verified_at'         => 'datetime',
        'approved_at'         => 'datetime',
        'disbursed_at'        => 'datetime',
        'offer_responded_by'  => 'integer',
        'offer_responded_at'  => 'datetime',
        'outstanding_balance' => 'decimal:2',
        'is_active'            => 'boolean',
        'status'                => LoanApplicationStatus::class,
        'assigned_reviewer_id'  => 'integer',
        'recommended_by_employee_id' => 'integer',
    ];

    /**
     * Relationship with the parent Application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * The reference a person should be shown for this loan: the application_no
     * off the parent Application row.
     *
     * Notifications and any other customer- or staff-facing text must use this
     * rather than the primary key. The two are close enough to be mistaken for
     * each other -- loan 353 carries application_id 354, and loan 354 exists as
     * well -- so quoting the id sends people to the wrong file. Falls back to
     * "#id" only for a loan with no application attached, which nothing in the
     * normal flow creates.
     */
    public function reference(): string
    {
        $this->loadMissing('application');

        return $this->application?->application_no ?? "#{$this->id}";
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
     * Relationship with the Group Loan this application belongs to, if any.
     * A group loan has exactly one loan application, not one per member.
     */
    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class);
    }

    /**
     * Whether this is a Group Loan's application. Group Loans carry no interest
     * rate at all — their repayment comes from the group's service charge
     * percentage — and must be driven through GroupLoanWorkflowService rather
     * than the individual-loan workflow endpoints.
     */
    public function isGroupLoan(): bool
    {
        return $this->group_loan_id !== null;
    }

    /**
     * Relationship with the User who applied.
     */
    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Relationship with the User who reviewed.
     */
    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Relationship with the User who verified — the second of the three
     * hands the file passes through before approval.
     */
    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Relationship with the User who approved.
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Who recorded the borrower's answer to the offer. */
    public function offerRespondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offer_responded_by');
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
     * Relationship with the customers attached to this loan application via the
     * pivot — Joint Loan co-borrowers, or a Group Loan's members. Empty for a
     * plain Individual Loan.
     */
    public function loanApplicationCustomers(): HasMany
    {
        return $this->hasMany(LoanApplicationCustomer::class);
    }

    /**
     * Whether this loan application has customers attached via the pivot, i.e.
     * it is a Joint Loan or a Group Loan rather than a plain Individual Loan.
     * Kept under this name because it gates the email notification channel,
     * which both Joint and Group Loans are meant to receive.
     */
    public function isJointLoan(): bool
    {
        return $this->loanApplicationCustomers()->exists();
    }

    /**
     * All customers who should receive notifications for this loan application:
     * every attached customer (Joint Loan co-borrowers or Group Loan members),
     * or just the primary customer otherwise.
     */
    public function notifiableCustomers(): \Illuminate\Support\Collection
    {
        if ($this->loanApplicationCustomers()->exists()) {
            return $this->loanApplicationCustomers()->with('customer')->get()->pluck('customer')->filter()->values();
        }

        return $this->customer ? collect([$this->customer]) : collect();
    }

    /**
     * The grace period that applies to this loan's installments: the product's
     * own, falling back to the system-wide default.
     */
    public function gracePeriodDays(): int
    {
        $this->loadMissing('loanProduct');

        // KNOWN LIMITATION, left deliberately as it is.
        //
        // `?:` treats 0 as "not set", so a product whose grace period is 0 gets
        // the installment_due_period_days setting (30) instead of no grace at
        // all. There is therefore no way to configure a product that is chased
        // from the day a payment is missed.
        //
        // Changing this to `??` is a one-word fix, but it is not only a fix: the
        // column is NOT NULL DEFAULT 0, so every product left untouched reads as
        // 0, and two live products here are in exactly that state. Making zero
        // mean zero would start chasing their borrowers and charging late fees a
        // month earlier than today, without anyone having asked for it. The
        // change therefore needs the affected products given an explicit grace
        // period first, which is a configuration decision rather than a code one.
        return (int) ($this->loanProduct?->grace_period_days
            ?: Setting::get('installment_due_period_days', 30));
    }

    /**
     * The installments still in arrears: money owed on a month whose grace
     * period has already elapsed.
     *
     * Deliberately independent of the installment's status stamp. A part
     * payment rewrites the row from 'overdue' to 'partially_paid' the instant
     * it lands, so anything keyed on the status treated a barely-reduced debt
     * as settled -- closing the recovery case and reverting the loan to Active
     * with most of the arrears still outstanding. The condition here is the
     * same one loans:mark-overdue uses to stamp 'overdue' in the first place.
     */
    public function arrearsInstallments()
    {
        $this->loadMissing('installments');
        $graceDays = $this->gracePeriodDays();

        return $this->installments->filter(
            fn ($installment) => $installment->balance > 0
                && $installment->due_date
                && $installment->due_date->copy()->addDays($graceDays)->lt(now())
        );
    }

    /**
     * Whether anything at all is still in arrears on this loan.
     */
    public function hasArrears(): bool
    {
        return $this->arrearsInstallments()->isNotEmpty();
    }

    /**
     * The parties in arrears on this loan, each paired with the earliest of
     * their own installments matching $filter.
     *
     * A Group Loan gives every member their own installment rows, so arrears
     * are per member: a group where 4 of 5 members paid on time yields exactly
     * one entry, for the member who did not. Each entry's own installment is
     * what drives that member's days-overdue, reminder cadence, penalty and
     * recovery case, so members never inherit each other's arrears.
     *
     * Individual and Joint Loans yield a single entry covering the whole loan
     * and every customer attached to it — unchanged behaviour. Note this keys
     * off isGroupLoan() rather than the installments' customer_id, because
     * those loans now stamp their primary customer on the row too and reading
     * the column would silently stop notifying Joint Loan co-borrowers.
     *
     * Expects `installments` (and, for a group loan,
     * `loanApplicationCustomers.customer`) to be loaded.
     *
     * @return array<int, array{customer_id: int|null, customers: \Illuminate\Support\Collection, installment: LoanInstallment}>
     */
    public function arrearsGroups(\Closure $filter): array
    {
        $matching = $this->installments->filter($filter);

        if ($matching->isEmpty()) {
            return [];
        }

        if ($this->isGroupLoan()) {
            $groups = [];

            foreach ($matching->whereNotNull('customer_id')->groupBy('customer_id') as $customerId => $installments) {
                $customer = $this->loanApplicationCustomers
                    ->firstWhere('customer_id', (int) $customerId)?->customer;

                if (!$customer) {
                    continue;
                }

                $groups[] = [
                    'customer_id' => (int) $customerId,
                    'customers'   => collect([$customer]),
                    'installment' => $installments->sortBy('due_date')->first(),
                ];
            }

            return $groups;
        }

        $notifyCustomers = $this->notifiableCustomers();

        if ($notifyCustomers->isEmpty()) {
            return [];
        }

        return [[
            'customer_id' => null,
            'customers'   => $notifyCustomers,
            'installment' => $matching->sortBy('due_date')->first(),
        ]];
    }

    /**
     * Relationship with the assigned reviewer.
     */
    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    /**
     * The documents collected for this application.
     *
     * Only the ones gathered against this application — a customer's own
     * papers (an NIC copy uploaded at registration) carry no
     * loan_application_id and belong to the customer's permanent file instead.
     */
    /** The legal agreements drawn up against this application. */
    public function legalDocuments(): HasMany
    {
        return $this->hasMany(LegalDocument::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * The CDP employee who recommended this customer.
     *
     * Null both for a loan taken before recommenders were recorded and for one
     * whose recommender has since been deleted -- the snapshot columns
     * (recommender_name and friends) are the record that survives either, so
     * read those for display and use this relation to walk back to the
     * employee's current file.
     */
    public function recommendedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recommended_by_employee_id');
    }

    /**
     * Relationship with the workflow status change history.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(LoanApplicationStatusHistory::class)->orderBy('changed_at')->orderBy('id');
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
     * Relationship with the loan revision ledger.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(LoanRevision::class)->orderBy('revision_no');
    }

    /**
     * Whether this loan application has a revision that is still pending approval,
     * which should block it from accepting new payments.
     */
    public function hasUnresolvedRevision(): bool
    {
        return $this->revisions()
            ->whereIn('status', array_map(fn ($status) => $status->value, LoanRevisionStatus::unresolved()))
            ->exists();
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to loan applications belonging to a given customer, whether as the
     * primary applicant or as a Joint Loan co-borrower attached via the pivot.
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where(function ($q) use ($customerId) {
            $q->where('customer_id', $customerId)
              ->orWhereHas('loanApplicationCustomers', function ($jointQuery) use ($customerId) {
                  $jointQuery->where('customer_id', $customerId);
              });
        });
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
                              ->orWhere('customer_code', 'like', "%$search%")
                              ->orWhere('id_number', 'like', "%$search%");
            })
            ->orWhereHas('loanApplicationCustomers.customer', function ($customerQuery) use ($search) {
                $customerQuery->where('full_name', 'like', "%$search%")
                              ->orWhere('customer_code', 'like', "%$search%")
                              ->orWhere('id_number', 'like', "%$search%");
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
