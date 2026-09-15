<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationBankDetail extends Model
{
    use HasFactory;

    protected $table = 'loan_application_bank_details';

    protected $fillable = [
        'loan_application_id',
        'customer_bank_detail_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id'     => 'integer',
        'customer_bank_detail_id' => 'integer',
    ];

    /**
     * Relationship with LoanApplication
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * Relationship with CustomerBankDetail
     */
    public function bankDetail(): BelongsTo
    {
        return $this->belongsTo(CustomerBankDetail::class, 'customer_bank_detail_id');
    }
}
