<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'recovery_case_id',
        'activity_type',
        'notes',
        'promised_amount',
        'promised_date',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'recovery_case_id' => 'integer',
        'promised_amount'   => 'decimal:2',
        'promised_date'     => 'date',
        'performed_by'      => 'integer',
        'performed_at'      => 'datetime',
    ];

    /**
     * Relationship with RecoveryCase
     */
    public function recoveryCase(): BelongsTo
    {
        return $this->belongsTo(RecoveryCase::class);
    }

    /**
     * Relationship with the user who performed the activity.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
