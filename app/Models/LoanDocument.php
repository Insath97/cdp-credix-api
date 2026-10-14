<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One document as used by one loan application, and who checked it there.
 *
 * The review and verify stamps live on this pair rather than on the document,
 * because a customer's papers are reused across every loan they take. See
 * create_loan_documents_table for the full reasoning.
 */
class LoanDocument extends Model
{
    protected $fillable = [
        'loan_application_id',
        'document_id',
        'reviewed_by',
        'reviewed_at',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'loan_application_id' => 'integer',
        'document_id'         => 'integer',
        'reviewed_by'         => 'integer',
        'reviewed_at'         => 'datetime',
        'verified_by'         => 'integer',
        'verified_at'         => 'datetime',
    ];

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
