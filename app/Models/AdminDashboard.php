<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminDashboard extends Model
{
    use HasFactory;

    /**
     * One row = one target/goal for a metric over a period. Table name kept
     * as dashboard_targets since that's what it stores, regardless of the
     * model's name.
     */
    protected $table = 'dashboard_targets';

    protected $fillable = [
        'metric',
        'target_value',
        'period_start',
        'period_end',
        'branch_id',
        'created_by',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'branch_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Relationship with the branch this target applies to (null = company-wide).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship with the user who set this target.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to the target row covering a given date (defaults to today),
     * optionally narrowed to a specific branch.
     */
    public function scopeForPeriod(Builder $query, string $metric, $date, ?int $branchId = null): Builder
    {
        $query->where('metric', $metric)
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date);

        return $branchId
            ? $query->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            : $query->whereNull('branch_id');
    }
}
