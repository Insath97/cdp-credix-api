<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovingAssests extends Model
{
    use SoftDeletes;

    protected $table = 'moving_assests';

    protected $fillable = [
        'customer_id',
        'assest_category',
        'owner_name',
        'make_model',
        'company_name',
        'no_of_shares',
        'par_value',
        'registation_no',
        'market_value',
        'mortgage_lease_hire_status',
        'is_active',
    ];

    protected $casts = [
        'no_of_shares' => 'integer',
        'par_value' => 'decimal:2',
        'market_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('assest_category', 'like', "%$search%")
              ->orWhere('owner_name', 'like', "%$search%")
              ->orWhere('registation_no', 'like', "%$search%");
        });
    }
}
