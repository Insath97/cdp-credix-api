<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'created_by',
        'phone_number',
        'snapshot_version',
        'snapshot_data',
        'otp_hash',
        'status',
        'attempts',
        'expires_at',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'snapshot_data'    => 'array',
        'snapshot_version' => 'integer',
        'attempts'         => 'integer',
        'expires_at'       => 'datetime',
        'verified_at'      => 'datetime',
    ];

    /**
     * The customer this verification belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The staff member who initiated this verification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The staff member who completed/verified this verification.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
