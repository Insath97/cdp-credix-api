<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerBankDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'bank_name',
        'branch_name',
        'account_number',
        'payment_method',
        'is_active',
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

}
