<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'customer_id',
        'user_id',
        'type',
        'channel',
        'recipient',
        'subject',
        'message',
        'status',
        'error',
        'sent_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'customer_id'          => 'integer',
        'user_id'              => 'integer',
        'sent_at'              => 'datetime',
    ];


    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
