<?php

namespace App\Models;

use App\Traits\UsedForCustomerLoan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovingAssests extends Model
{
    use SoftDeletes, UsedForCustomerLoan;

    protected $table = 'moving_assests';

    protected $fillable = [
        'slug',
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

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'no_of_shares' => 'integer',
        'par_value' => 'decimal:2',
        'market_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['used_for_loan'];

    /**
     * Auto-generate a unique slug (AST-MOV-0001 style) when one isn't provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movingAsset) {
            if (empty($movingAsset->slug)) {
                $lastAsset = self::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastAsset ? $lastAsset->id + 1 : 1;
                $movingAsset->slug = 'AST-MOV-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }

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
