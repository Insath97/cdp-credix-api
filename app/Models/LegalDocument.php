<?php

namespace App\Models;

use App\Enums\LegalDocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_application_id',
        'legal_document_template_id',
        'reference_no',
        'document_type',
        'language',
        'status',
        'legal_document_created_date',
        'created_by',
        'details',
        'printed_count',
        'last_printed_at',
        'remarks',
        'is_active',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'loan_application_id'         => 'integer',
        'legal_document_template_id'  => 'integer',
        'created_by'                  => 'integer',
        'status'                      => LegalDocumentStatus::class,
        'legal_document_created_date' => 'datetime',
        'details'                     => 'array',
        'printed_count'               => 'integer',
        'last_printed_at'             => 'datetime',
        'is_active'                   => 'boolean',
    ];

    protected $appends = ['document_type_label', 'status_label'];

    public function getDocumentTypeLabelAttribute(): string
    {
        return LegalDocumentTemplate::TYPES[$this->document_type] ?? (string) $this->document_type;
    }

    public function getStatusLabelAttribute(): ?string
    {
        return $this->status?->label();
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentTemplate::class, 'legal_document_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!filled($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reference_no', 'like', "%{$term}%")
                ->orWhere('document_type', 'like', "%{$term}%")
                ->orWhereHas(
                    'loanApplication.application',
                    fn (Builder $a) => $a->where('application_no', 'like', "%{$term}%")
                )
                ->orWhereHas(
                    'loanApplication.customer',
                    fn (Builder $c) => $c->where('full_name', 'like', "%{$term}%")
                        ->orWhere('customer_code', 'like', "%{$term}%")
                );
        });
    }

    /**
     * The next reference for a legal document: LEG-{000001}.
     *
     * Taken under a lock and ordered by length first, so 10 sorts after 9
     * rather than after 1 -- the same rule ReferenceNumberService uses for the
     * application and approval references.
     */
    public static function nextReference(): string
    {
        $last = static::withTrashed()
            ->lockForUpdate()
            ->where('reference_no', 'like', 'LEG-%')
            ->orderByRaw('LENGTH(reference_no) DESC')
            ->orderBy('reference_no', 'desc')
            ->value('reference_no');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'LEG-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
