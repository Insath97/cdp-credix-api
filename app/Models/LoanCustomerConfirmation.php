<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's own confirmation of a loan application (OTP 2), made on the
 * public review page reached through an SMS link. One row per customer per
 * link sent; a Joint Loan gets one row per co-borrower.
 */
class LoanCustomerConfirmation extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_SUPERSEDED = 'superseded';
    public const STATUS_EXPIRED    = 'expired';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'loan_application_id',
        'customer_id',
        'created_by',
        'phone_number',
        'token_hash',
        'link_expires_at',
        'sms_sent',
        'viewed_at',
        'snapshot_data',
        'snapshot_hash',
        'otp_hash',
        'otp_expires_at',
        'otp_attempts',
        'otp_requests',
        'status',
        'declaration_text',
        'confirmed_at',
        'confirmed_ip',
        'confirmed_user_agent',
    ];

    protected $hidden = [
        'token_hash',
        'otp_hash',
        'snapshot_data',
    ];

    protected $casts = [
        'snapshot_data'   => 'array',
        'sms_sent'        => 'boolean',
        'otp_attempts'    => 'integer',
        'otp_requests'    => 'integer',
        'link_expires_at' => 'datetime',
        'viewed_at'       => 'datetime',
        'otp_expires_at'  => 'datetime',
        'confirmed_at'    => 'datetime',
    ];

    /**
     * The loan application being confirmed.
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * The customer who confirms.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The staff member who sent the review link.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLinkExpired(): bool
    {
        return $this->link_expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
