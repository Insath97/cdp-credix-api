<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Document extends Model
{
    use HasFactory, SoftDeletes;

   
    public const TYPES = [
        'nic_copy'                        => 'NIC Copy',
        'passport_copy'                   => 'Passport Copy',
        'driving_license'                 => 'Driving License',
        'salary_slip'                     => 'Salary Slips (last 3 months)',
        'salary_confirmation_letter'      => 'Salary Confirmation Letter',
        'employment_confirmation_letter'  => 'Employment Confirmation Letter',
        'bank_statement'                  => 'Bank Statement (last 6 months)',
        'gs_division_certificate'         => 'GS Division Confirmation',
        'ds_division_certificate'         => 'DS Division Confirmation',
        'billing_proof'                   => 'Billing Proof',
        'salary_assignment_letter'        => 'Salary Assignment Letter',
        'employer_letter'                 => 'Employer Letter',
        'photo'                           => 'Photo',
        'other'                           => 'Other',
    ];

    protected $fillable = [
        'document_type',
        'is_mandatory',
        'document_name',
        'file_path',
        'customer_id',
        'loan_application_id',
        'guarantor_id',
        'uploaded_by',
        'uploaded_at',
        'status',
        'remarks',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'loan_application_id' => 'integer',
        'guarantor_id' => 'integer',
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
     * The loan application this document was collected for, if any.
     *
     * Null for a document that belongs to the customer's permanent file (an
     * NIC copy) rather than to one application (that application's pay slips).
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * The guarantor this document belongs to, if any.
     */
    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The applications this document forms part of, with the review and verify
     * stamps each of them recorded against it.
     *
     * A customer's papers follow them across every loan they take, so "who
     * checked this" is a fact about the pair, not about the file. Reading it
     * off the document row -- which is what the dropped reviewed_by column
     * did -- could only ever answer for whichever loan looked at it last.
     */
    public function loanDocuments(): HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function loanApplications(): BelongsToMany
    {
        return $this->belongsToMany(LoanApplication::class, 'loan_documents')
            ->withPivot(['reviewed_by', 'reviewed_at', 'verified_by', 'verified_at'])
            ->withTimestamps();
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
