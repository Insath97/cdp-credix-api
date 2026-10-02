<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalDocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /** The languages an agreement may be approved in. */
    public const LANGUAGES = [
        'en' => 'English',
        'ta' => 'Tamil',
        'si' => 'Sinhala',
    ];

    protected $fillable = [
        'loan_type_id',
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
        'loan_type_id' => 'integer',
        'created_by'   => 'integer',
        'is_active'    => 'boolean',
    ];

    protected $appends = ['document_type_label', 'language_label'];

    public function getDocumentTypeLabelAttribute(): string
    {
        return LegalDocumentType::labelFor($this->document_type);
    }

    public function getLanguageLabelAttribute(): string
    {
        return self::LANGUAGES[$this->language] ?? (string) $this->language;
    }

    /** The loan type whose applications are drawn up on this template. */
    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    /**
     * Active templates of a loan application's loan type
     * (application -> product -> loan type).
     */
    public function scopeForLoanApplication(Builder $query, LoanApplication $loanApplication): Builder
    {
        $loanApplication->loadMissing('loanProduct');

        return $query->where('is_active', true)
            ->where('loan_type_id', $loanApplication->loanProduct?->loan_type_id);
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
                ->orWhereHas('loanType', fn (Builder $t) => $t->where('title', 'like', "%{$term}%"));
        });
    }
}
