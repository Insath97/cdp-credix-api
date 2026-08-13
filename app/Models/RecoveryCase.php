<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecoveryCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'case_no',
        'status',
        'stage',
        'overdue_amount',
        'assigned_agent_id',
        'external_agent_id',
        'parent_case_id',
        'opened_by',
        'remarks',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'overdue_amount'       => 'decimal:2',
        'assigned_agent_id'    => 'integer',
        'external_agent_id'    => 'integer',
        'parent_case_id'       => 'integer',
        'opened_by'            => 'integer',
        'opened_at'            => 'datetime',
        'closed_at'            => 'datetime',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with the assigned recovery agent.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Relationship with the user who opened the case.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Relationship with the recovery activity timeline.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecoveryActivity::class)->orderBy('performed_at');
    }

    /**
     * Relationship with the assigned external recovery agent.
     */
    public function externalAgent(): BelongsTo
    {
        return $this->belongsTo(ExternalRecoveryAgent::class);
    }

    /**
     * Relationship with the case this one was escalated from (e.g. the
     * closed internal case that preceded an external-recovery case).
     */
    public function parentCase(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_case_id');
    }

    /**
     * Relationship with the case this one was escalated into.
     */
    public function childCase(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(self::class, 'parent_case_id');
    }
}
