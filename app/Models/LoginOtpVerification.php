<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginOtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'otp',
        'expires_at',
        'status',
        'verified_at',
        'ip_address',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'otp',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
