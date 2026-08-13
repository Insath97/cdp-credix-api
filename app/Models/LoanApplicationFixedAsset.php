<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationFixedAsset extends Model
{
    use HasFactory;

    protected $table = 'loan_application_fixed_assets';

    protected $fillable = [
        'loan_application_id',
        'fixed_assest_id',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'fixed_assest_id'     => 'integer',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with FixedAssests
     */
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAssests::class, 'fixed_assest_id');
    }
}
