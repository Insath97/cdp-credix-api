<?php

namespace App\Models;

use App\Enums\LoanApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guarantor extends Model
{
    use HasFactory;

    /**
     * Columns to select when a guarantor is loaded as a nested reference.
     *
     * Excludes the guarantor's NIC, ID image, date of birth, salary, allowances,
     * other income, declared liabilities and bank account details — none of which
     * a loan or application screen needs in order to list who is guaranteeing.
     *
     * @see Customer::SUMMARY_COLUMNS
     */
    public const SUMMARY_COLUMNS = 'id,customer_id,full_name,type,phone_primary,occupation';

    protected $appends = ['used_for_loan'];

    protected $fillable = [
        'customer_id',
        'full_name',
        'type',
        'id_type',
        'id_number',
        'id_image',
        'date_of_birth',
        'phone_primary',
        'occupation',
        'employer_name',
        'date_joined',
        'salary',
        'allowance',
        'other_income',
        'liabilities',
        'bank_name_of_guarantor',
        'bank_account_no_of_guarantor',
        'bank_branch_of_guarantor',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_joined' => 'date',
        'salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'other_income' => 'decimal:2',
        'liabilities' => 'decimal:2',
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%")
                ->orWhere('id_type', 'like', "%$search%")
                ->orWhere('id_number', 'like', "%$search%")
                ->orWhere('phone_primary', 'like', "%$search%")
                ->orWhere('occupation', 'like', "%$search%")
                ->orWhere('employer_name', 'like', "%$search%")
                ->orWhere('bank_name_of_guarantor', 'like', "%$search%")
                ->orWhere('bank_account_no_of_guarantor', 'like', "%$search%")
                ->orWhere('bank_branch_of_guarantor', 'like', "%$search%");
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Loan applications this guarantor has been pledged against.
     */
    public function loanApplications(): BelongsToMany
    {
        return $this->belongsToMany(LoanApplication::class, 'loan_application_guarantors', 'guarantor_id', 'loan_application_id');
    }

    /**
     * Whether this guarantor is currently pledged to a loan application that
     * hasn't reached a terminal (released) status.
     */
    public function getUsedForLoanAttribute(): bool
    {
        return $this->loanApplications()
            ->whereNotIn('loan_applications.status', [
                LoanApplicationStatus::Rejected->value,
                LoanApplicationStatus::Cancelled->value,
                LoanApplicationStatus::Closed->value,
            ])
            ->exists();
    }

}
