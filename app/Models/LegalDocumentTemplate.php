<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalDocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The agreements the legal desk has drafted.
     *
     * Keys are what the API validates and stores; the labels are what a screen
     * prints. Kept here rather than in a database enum so adding the next
     * agreement is a one-line change, not an ALTER.
     */
    public const TYPES = [
        'direct_loan_agreement'            => 'Direct Loan Agreement With Two Guarantors',
        'loan_agreement_investment'        => 'Loan Agreement Against Investment',
        'loan_application_acknowledgement' => 'Loan Application & Acknowledgement Against Investment',
    ];

    /** The languages an agreement may be approved in. */
    public const LANGUAGES = [
        'en' => 'English',
        'ta' => 'Tamil',
        'si' => 'Sinhala',
    ];

    protected $fillable = [
        'loan_product_id',
        'document_type',
        'language',
        'title',
        'description',
        'file_path',
        'source_file_name',
        'content',
        'created_by',
        'is_active',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'loan_product_id' => 'integer',
        'created_by'      => 'integer',
        'is_active'       => 'boolean',
    ];

    protected $appends = ['document_type_label', 'language_label'];

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::TYPES[$this->document_type] ?? (string) $this->document_type;
    }

    public function getLanguageLabelAttribute(): string
    {
        return self::LANGUAGES[$this->language] ?? (string) $this->language;
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(LegalDocument::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!filled($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('document_type', 'like', "%{$term}%")
                ->orWhere('source_file_name', 'like', "%{$term}%")
                ->orWhereHas('loanProduct', fn (Builder $p) => $p->where('name', 'like', "%{$term}%"));
        });
    }
}
