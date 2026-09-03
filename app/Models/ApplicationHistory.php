<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationHistory extends Model
{
    use HasFactory;

    protected $table = 'application_history';

    protected $fillable = [
        'application_id',
        'customer_id',
        'application_no',
        'application_type',
        'role',
        'status',
        'basic_salary',
        'fixed_allowances',
        'other_allowances',
        'other_income',
        'total_monthly_income',
        'household_expenses',
        'rent_expense',
        'insurance_premiums',
        'other_expenses',
        'total_monthly_expenses',
        'requested_amount',
        'purpose',
        'recorded_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'fixed_allowances' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'other_income' => 'decimal:2',
        'total_monthly_income' => 'decimal:2',
        'household_expenses' => 'decimal:2',
        'rent_expense' => 'decimal:2',
        'insurance_premiums' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'total_monthly_expenses' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('application_no', 'like', "%$search%")
                ->orWhere('application_type', 'like', "%$search%")
                ->orWhere('role', 'like', "%$search%")
                ->orWhere('status', 'like', "%$search%");
        });
    }
}
