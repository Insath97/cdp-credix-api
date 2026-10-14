<?php

namespace App\Models;

use App\Traits\UsedForCustomerLoan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssests extends Model
{
    use SoftDeletes, UsedForCustomerLoan;

    protected $table = 'fixed_assests';

    protected $fillable = [
        'slug',
        'customer_id',
        'owner_name',
        'property_location',
        'extent',
        'market_value',
        'is_mortaged',
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

    protected $appends = ['used_for_loan'];

    /**
     * Auto-generate a unique slug (AST-FIX-0001 style) when one isn't provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fixedAsset) {
            if (empty($fixedAsset->slug)) {
                $lastAsset = self::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastAsset ? $lastAsset->id + 1 : 1;
                $fixedAsset->slug = 'AST-FIX-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
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

    public function scopeSearch($query, $term)
    {
        return $query->where('owner_name', 'like', "%$term%")
            ->orWhere('property_location', 'like', "%$term%")
            ->orWhere('extent', 'like', "%$term%")
            ->orWhere('market_value', 'like', "%$term%");
    }
}
