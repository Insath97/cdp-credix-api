<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationStatusHistory extends Model
{
    protected $table = 'loan_application_status_history';

    protected $fillable = [
        'loan_application_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
        'metadata',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'changed_by'          => 'integer',
        'metadata'             => 'array',
    ];

    /**
     * Relationship with the LoanApplication this history entry belongs to.
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with the User who performed the transition.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
