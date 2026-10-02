<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One legal document a loan product's loans need. */
class LoanProductLegalDocument extends Model
{
    protected $fillable = ['loan_product_id', 'document_type'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'loan_product_id' => 'integer',
        'document_type'   => LegalDocumentType::class,
    ];

    protected $appends = ['document_type_label'];

    public function getDocumentTypeLabelAttribute(): ?string
    {
        return $this->document_type?->label();
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }
}
