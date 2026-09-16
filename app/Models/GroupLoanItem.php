<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupLoanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_loan_id',
        'item_name',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'group_loan_id' => 'integer',
        'quantity'      => 'integer',
        'unit_price'    => 'decimal:2',
        'line_total'    => 'decimal:2',
    ];

    public function groupLoan(): BelongsTo
    {
        return $this->belongsTo(GroupLoan::class);
    }
}
