<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationMovingAsset extends Model
{
    use HasFactory;

    protected $table = 'loan_application_moving_assets';

    protected $fillable = [
        'loan_application_id',
        'moving_assest_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'moving_assest_id'    => 'integer',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with MovingAssests
     */
    public function movingAsset(): BelongsTo
    {
        return $this->belongsTo(MovingAssests::class, 'moving_assest_id');
    }
}
