<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDetail extends Model
{
    protected $fillable = [
        'customer_id',
        'gn_division',
        'ds_division',
        'district',
        'province',
    ];

    protected $casts = [
        'customer_id' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
