<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LegalDocumentSignature extends Model
{
    /** The private disk: signature images are never served from the web root. */
    public const DISK = 'local';

    protected $fillable = [
        'legal_document_id',
        'signer_key',
        'signer_role',
        'customer_id',
        'guarantor_id',
        'signer_name',
        'signer_nic',
        'signer_designation',
        'signature_path',
        'signature_mime',
        'signature_sha256',
        'document_hash',
        'signed_at',
        'captured_by',
        'ip_address',
        'user_agent',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $hidden = ['signature_path'];

    protected $casts = [
        'legal_document_id' => 'integer',
        'customer_id'       => 'integer',
        'guarantor_id'      => 'integer',
        'captured_by'       => 'integer',
        'voided_by'         => 'integer',
        'signed_at'         => 'datetime',
        'voided_at'         => 'datetime',
    ];

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }

    public function capturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    /** The stored image, or null when the file is missing. */
    public function imageBytes(): ?string
    {
        $disk = Storage::disk(self::DISK);

        return $disk->exists($this->signature_path) ? $disk->get($this->signature_path) : null;
    }
}
