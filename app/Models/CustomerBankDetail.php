<?php

namespace App\Models;

use App\Enums\LoanApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerBankDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['used_for_loan'];

    protected $fillable = [
        'customer_id',
        'bank_name',
        'branch_name',
        'account_number',
        'payment_method',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('bank_name', 'like', "%$search%")
                ->orWhere('branch_name', 'like', "%$search%")
                ->orWhere('account_number', 'like', "%$search%")
                ->orWhere('payment_method', 'like', "%$search%");
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Loan applications this bank detail has been linked to.
     */
    public function loanApplications(): BelongsToMany
    {
        return $this->belongsToMany(LoanApplication::class, 'loan_application_bank_details', 'customer_bank_detail_id', 'loan_application_id');
    }

    /**
     * Whether this bank detail is currently linked to a loan application that
     * hasn't reached a terminal (released) status.
     */
    public function getUsedForLoanAttribute(): bool
    {
        return $this->loanApplications()
            ->whereNotIn('status', [
                LoanApplicationStatus::Rejected->value,
                LoanApplicationStatus::Cancelled->value,
                LoanApplicationStatus::Closed->value,
            ])
            ->exists();
    }

}
