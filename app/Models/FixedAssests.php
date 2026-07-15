<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssests extends Model
{
    use SoftDeletes;

    protected $table = 'fixed_assests';

    protected $fillable = [
        'customer_id',
        'owner_name',
        'property_location',
        'extent',
        'market_value',
        'is_mortaged',
        'is_active',
    ];

    protected $casts = [
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

    public function scopeSearch($query, $term)
    {
        return $query->where('owner_name', 'like', "%$term%")
            ->orWhere('property_location', 'like', "%$term%")
            ->orWhere('extent', 'like', "%$term%")
            ->orWhere('market_value', 'like', "%$term%");
    }
}
