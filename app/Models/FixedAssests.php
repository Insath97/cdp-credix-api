<?php

namespace App\Models;

use App\Enums\LoanApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FixedAssests extends Model
{
    use SoftDeletes;

    protected $table = 'fixed_assests';

    protected $fillable = [
        'slug',
        'customer_id',
        'owner_name',
        'property_location',
        'extent',
        'market_value',
        'is_mortaged',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['used_for_loan'];

    /**
     * Auto-generate a unique slug (AST-FIX-0001 style) when one isn't provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fixedAsset) {
            if (empty($fixedAsset->slug)) {
                $lastAsset = self::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastAsset ? $lastAsset->id + 1 : 1;
                $fixedAsset->slug = 'AST-FIX-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Loan applications this asset has been pledged against.
     */
    public function loanApplications(): BelongsToMany
    {
        return $this->belongsToMany(LoanApplication::class, 'loan_application_fixed_assets', 'fixed_assest_id', 'loan_application_id');
    }

    /**
     * Whether this asset is currently pledged to a loan application that
     * hasn't reached a terminal (released) status.
     */
    public function getUsedForLoanAttribute(): bool
    {
        return $this->loanApplications()
            ->whereNotIn('status', [
                LoanApplicationStatus::Rejected->value,
                LoanApplicationStatus::Cancelled->value,
                LoanApplicationStatus::Closed->value,
            ])
            ->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('owner_name', 'like', "%$term%")
            ->orWhere('property_location', 'like', "%$term%")
            ->orWhere('extent', 'like', "%$term%")
            ->orWhere('market_value', 'like', "%$term%");
    }
}
