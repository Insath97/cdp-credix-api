<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liability extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'liability_type',
        'institution_name',
        'account_reference_no',
        'original_amount',
        'outstanding_balance',
        'monthly_installment',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('institution_name', 'like', "%{$search}%")
              ->orWhere('liability_type', 'like', "%{$search}%")
              ->orWhere('account_reference_no', 'like', "%{$search}%");
        });
    }
}
