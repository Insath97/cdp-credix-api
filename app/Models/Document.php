<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_type',
        'is_mandatory',
        'document_name',
        'file_path',
        'customer_id',
        'uploaded_by',
        'uploaded_at',
        'status',
        'remarks',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the customer this document belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for searching.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('document_name', 'like', "%$search%")
                ->orWhere('document_type', 'like', "%$search%")
                ->orWhere('status', 'like', "%$search%")
                ->orWhere('remarks', 'like', "%$search%");
        });
    }
}
