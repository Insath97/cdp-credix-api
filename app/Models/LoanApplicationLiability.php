<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationLiability extends Model
{
    use HasFactory;

    protected $table = 'loan_application_liabilities';

    protected $fillable = [
        'loan_application_id',
        'liability_id',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'liability_id'        => 'integer',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with Liability
     */
    public function liability(): BelongsTo
    {
        return $this->belongsTo(Liability::class);
    }
}
