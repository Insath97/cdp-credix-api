<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\CreditScoreService;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Columns to select when a customer is loaded as a nested reference — on a
     * loan, a payment, a recovery case — rather than as the record being viewed.
     *
     * Those screens only need to name the customer, so the NIC, date of birth,
     * home address, income figures and employer/business details are never
     * queried and so can never reach the browser. Endpoints whose subject IS the
     * customer (CustomerController, CustomerProfileController) select normally.
     *
     * Pass to a relation string: ->with('customer:'.Customer::SUMMARY_COLUMNS)
     */
    public const SUMMARY_COLUMNS = 'id,customer_id,customer_code,full_name,name_with_initials,phone_primary,branch_id,current_application_id,applicant_role,credit_score,credit_score_on_time_rate,credit_score_updated_at,is_active';

    /**
     * SUMMARY_COLUMNS plus the means to reach the customer — for recovery and
     * collections screens, where an officer has to call or visit a defaulter.
     *
     * Still withholds the NIC, date of birth, income figures and the employer /
     * business profile, none of which are needed to make contact.
     */
    public const CONTACT_COLUMNS = self::SUMMARY_COLUMNS
        .',phone_secondary,email,have_whatsapp,whatsapp_number'
        .',address_line_1,address_line_2,landmark,city,state,country,postal_code';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_id)) {
                $lastCustomer = self::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
                $customer->customer_id = 'CUST-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
            if (empty($customer->customer_code)) {
                $lastCustomer = self::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
                $customer->customer_code = 'CUS' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $fillable = [
        'customer_id',
        'customer_code',
        'full_name',
        'name_with_initials',
        'id_type',
        'id_number',
        'date_of_birth',
        'address_line_1',
        'address_line_2',
        'landmark',
        'city',
        'state',
        'country',
        'postal_code',
        'phone_primary',
        'phone_secondary',
        'email',
        'have_whatsapp',
        'whatsapp_number',
        'preferred_language',

        // employment
        'employment_status',
        'occupation',
        'employer_name',
        'employer_address_line1',
        'employer_address_line2',
        'employer_city',
        'employer_state',
        'employer_country',
        'employer_postal_code',
        'employer_phone',
        'employer_email',
        'monthly_income',

        // business
        'business_name',
        'business_registration_number',
        'business_nature',
        'business_address_line1',
        'business_address_line2',
        'business_city',
        'business_state',
        'business_country',
        'business_postal_code',
        'business_phone',
        'business_email',

        // current application snapshot
        'branch_id',
        'applicant_role',
        'current_application_id',
        'fixed_allowances',
        'other_allowances',
        'other_income',
        'total_monthly_income',
        'other_expenses',
        'total_monthly_expenses',

        // the CDP employee who introduced this customer
        'recommended_by_employee_id',
        'recommender_name',
        'recommender_employee_code',
        'recommender_nic',
        'recommender_phone',

        // repayment credit score (written only by CreditScoreService)
        'credit_score',
        'credit_score_on_time_rate',
        'credit_score_updated_at',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'is_active' => 'boolean',
        'date_of_birth' => 'date',
        'have_whatsapp' => 'boolean',
        'monthly_income' => 'decimal:2',
        'fixed_allowances' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'other_income' => 'decimal:2',
        'total_monthly_income' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'total_monthly_expenses' => 'decimal:2',
        'recommended_by_employee_id' => 'integer',
        'credit_score' => 'decimal:2',
        'credit_score_on_time_rate' => 'decimal:2',
        'credit_score_updated_at' => 'datetime',
    ];

    /**
     * Appended so every screen that already receives a customer -- the loan
     * application review, the customer profile, the customer list -- can render
     * the score band without a second request and without reimplementing the
     * thresholds. Those thresholds live in CreditScoreService::band() alone;
     * duplicating them in the frontend would let the two drift, and the scale
     * they are measured against is itself a System Setting.
     */
    protected $appends = ['credit_score_band'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%$search%")
                ->orWhere('customer_code', 'like', "%$search%")
                ->orWhere('id_number', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('phone_primary', 'like', "%$search%");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function currentApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'current_application_id');
    }

    public function bankDetails(): HasMany
    {
        return $this->hasMany(CustomerBankDetail::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAssests::class, 'customer_id');
    }

    public function movingAssets(): HasMany
    {
        return $this->hasMany(MovingAssests::class, 'customer_id');
    }

    public function liabilities(): HasMany
    {
        return $this->hasMany(Liability::class, 'customer_id');
    }

    public function applicationHistories(): HasMany
    {
        return $this->hasMany(ApplicationHistory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function customerDetail(): HasOne
    {
        return $this->hasOne(CustomerDetail::class);
    }

    /**
     * The CDP employee who introduced this customer.
     *
     * The standing introducer on the file. A particular loan can have been put
     * forward by someone else -- that one lives on
     * LoanApplication::recommendedByEmployee(). Read the snapshot columns for
     * display; this relation is for walking back to the employee's file.
     */
    public function recommendedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recommended_by_employee_id');
    }

    public function creditScores(): HasMany
    {
        return $this->hasMany(CustomerCreditScore::class);
    }

    public function creditScoreEvents(): HasMany
    {
        return $this->hasMany(CreditScoreEvent::class);
    }

    /**
     * The score's plain-language band: excellent / good / fair / poor /
     * very_poor, or null.
     *
     * Null covers two cases the UI must not conflate with a bad score: a
     * customer with no repayment history at all, and a query that did not
     * select the credit_score column in the first place.
     */
    public function getCreditScoreBandAttribute(): ?string
    {
        // Read off the on-time rate, not the score: the score is a point
        // total that grows with the length of the record, so it cannot say on
        // its own how reliably this person pays.
        if (!array_key_exists('credit_score_on_time_rate', $this->attributes)
            || $this->credit_score_on_time_rate === null) {
            return null;
        }

        return app(CreditScoreService::class)->band((float) $this->credit_score_on_time_rate);
    }

    /**
     * Whether this customer has ever had an installment judged.
     *
     * Read this before showing the score anywhere: a null credit_score means
     * "no repayment history yet", which must not be rendered as a zero.
     */
    public function hasCreditHistory(): bool
    {
        return $this->credit_score !== null;
    }
}
