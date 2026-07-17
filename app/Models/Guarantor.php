<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use HasFactory;

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

}
