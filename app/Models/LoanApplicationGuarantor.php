<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationGuarantor extends Model
{
    use HasFactory;

    protected $table = 'loan_application_guarantors';

    protected $fillable = [
        'loan_application_id',
        'guarantor_id',
        'guarantor_type',
        'status',
        'remarks',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'guarantor_id'        => 'integer',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with Guarantor
     */
    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }
}
