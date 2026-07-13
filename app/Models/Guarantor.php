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
        'phone_primary',
        'id_image'
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%")
                ->orWhere('id_type', 'like', "%$search%")
                ->orWhere('id_number', 'like', "%$search%")
                ->orWhere('phone_primary', 'like', "%$search%");
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
