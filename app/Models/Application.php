<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\ReferenceNumberService;

class Application extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->application_no)) {
                // APP-{BRANCH}-{yymmdd}{0001}. The format and the branch code
                // rules live in ReferenceNumberService, which also mints the
                // approval reference, so the two cannot drift apart.
                //
                // Numbers issued under either older format are deliberately
                // left alone: they are printed on paper, quoted in SMS already
                // sent, and referenced from payments. nextSequence() is what
                // keeps them from being mistaken for the new counter.
                $application->application_no = app(ReferenceNumberService::class)
                    ->forApplication($application->branch);
            }
        });
    }

    protected $fillable = [
        'application_no',
        'application_type',
        'branch',
        'loan_type',
        'requested_amount',
        'purpose',
        'repayment_period_months',
        'monthly_repayment_date',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'repayment_period_months' => 'integer',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'current_application_id');
    }

    public function applicationHistories(): HasMany
    {
        return $this->hasMany(ApplicationHistory::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('application_no', 'like', "%$search%")
                ->orWhere('application_type', 'like', "%$search%")
                ->orWhere('branch', 'like', "%$search%")
                ->orWhere('loan_type', 'like', "%$search%")
                ->orWhere('status', 'like', "%$search%");
        });
    }
}
