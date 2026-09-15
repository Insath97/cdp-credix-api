<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecoveryAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'is_active',
        'remarks',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'user_id'   => 'integer',
        'branch_id' => 'integer',
        'is_active'  => 'boolean',
    ];

    /**
     * Relationship with the User account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with the Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship with recovery cases assigned to this agent's user account.
     */
    public function recoveryCases(): HasMany
    {
        return $this->hasMany(RecoveryCase::class, 'assigned_agent_id', 'user_id');
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
