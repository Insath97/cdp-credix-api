<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupLoanMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_loan_id',
        'loan_application_id',
        'customer_id',
        'member_name',
        'nic',
        'address',
        'phone_number',
        'gn_division',
        'ds_division',
    ];

    protected $casts = [
        'group_loan_id'       => 'integer',
        'loan_application_id' => 'integer',
        'customer_id'         => 'integer',
    ];

    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
