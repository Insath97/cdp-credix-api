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
        'overdue_amount',
        'assigned_agent_id',
        'opened_by',
        'remarks',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'overdue_amount'       => 'decimal:2',
        'assigned_agent_id'    => 'integer',
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
}
