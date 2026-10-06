<?php

namespace App\Models;

use App\Enums\LoanSecurityType;
use App\Services\LoanSecurityPlanService;
use App\Models\LendingMortgage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One thing pledged against a loan application -- a CDP Investment, a Property
 * Mortgage or a Vehicle. A secured loan has one or more of these
 * (LoanApplication::securities()); each is an independent item with its own
 * details, its own value and its own papers. Required on every Standard
 * Borrowing loan under the General term, Individual or Joint
 * (LoanProduct::requiresSecurity()).
 *
 * The value is pledged_value for every type, whatever the form called it: a
 * property's estimated_value, a vehicle's vehicle_value, or -- for a CDP
 * Investment, which has no value field at all -- what CDP Core reported and
 * LoanSecurityService recorded in investment_details. pledged_value is what the
 * configured percentage is applied to, so the loan's coverage is a sum of the
 * same number on every row (LoanSecurityLtvService).
 *
 * Its supporting papers are ordinary documents rows on the same application,
 * so they sit in the review/verify checklist and freeze at disbursement like
 * every other document.
 */
class LoanApplicationSecurity extends Model
{
    /** A fixed list rather than free text, so reports can group by it. */
    public const PROPERTY_TYPES = [
        'land'                => 'Land',
        'house'               => 'House',
        'land_and_building'   => 'Land and Building',
        'apartment'           => 'Apartment / Condominium',
        'commercial_building' => 'Commercial Building',
        'other'               => 'Other',
    ];

    protected $fillable = [
        'loan_application_id',
        'security_type',
        'lending_mortgage_id',
        'investment_nic',
        'policy_number',
        'investment_details',
        'owner_name',
        'property_type',
        'owner_deed_number',
        'estimated_value',
        'evaluation_date',
        'evaluated_by',
        'evaluation_remarks',
        'vehicle_make',
        'vehicle_model',
        'year_of_manufacture',
        'year_of_registration',
        'registration_number',
        'chassis_engine_number',
        'vehicle_value',
        // Written by LoanSecurityService from the plan and the value, never
        // from the request. Not authority in the frontend's hands.
        'pledged_value',
        'max_loan_percentage',
        'max_loan_amount',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'loan_application_id'  => 'integer',
        'lending_mortgage_id'  => 'integer',
        'security_type'        => LoanSecurityType::class,
        'investment_details'   => 'array',
        'estimated_value'      => 'decimal:2',
        'evaluation_date'      => 'date:Y-m-d',
        'year_of_manufacture'  => 'integer',
        'year_of_registration' => 'integer',
        'vehicle_value'        => 'decimal:2',
        'pledged_value'        => 'decimal:2',
        'max_loan_percentage'  => 'decimal:2',
        'max_loan_amount'      => 'decimal:2',
    ];

    public function lendingMortgage()
    {
        return $this->belongsTo(LendingMortgage::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * What this security is worth, or null when it carries no value -- a
     * vehicle that has not been valued yet, or a CDP Investment CDP Core did
     * not report a value for. Null rather than zero on purpose: zero is a
     * security worth nothing, null is a security whose value is not known, and
     * only the second is worth telling the officer about.
     */
    public function value(): ?float
    {
        if ($this->pledged_value !== null) {
            return round((float) $this->pledged_value, 2);
        }

        // Rows written before pledged_value existed, or a security saved
        // without it: fall back to the type's own column so an old record is
        // not read as having no value at all.
        $field = $this->security_type?->valueField();

        if ($field === null) {
            $amount = $this->investment_details['investment_amount'] ?? null;

            return is_numeric($amount) ? round((float) $amount, 2) : null;
        }

        $value = $this->getAttributes()[$field] ?? null;

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    /**
     * The most this security can secure, from the percentage snapshotted onto
     * the row. Recomputed from the plan on the row when it has no snapshot, so a
     * security written before the columns existed still measures correctly
     * rather than reading as worth nothing.
     *
     * A row that names no plan and has no snapshot has nothing to fall back to,
     * and returns null. It is not read against a type-wide default: that is the
     * rate the officer did not choose, and applying it silently would lend
     * against a security under terms nobody picked.
     */
    public function eligibleValue(): ?float
    {
        $value = $this->value();

        if ($value === null || $this->security_type === null) {
            return null;
        }

        if ($this->max_loan_amount !== null) {
            return round((float) $this->max_loan_amount, 2);
        }

        $percentage = $this->max_loan_percentage !== null
            ? (float) $this->max_loan_percentage
            : app(LoanSecurityPlanService::class)->percentageFor($this->security_type, $this->security_plan);

        return $percentage === null ? null : round($value * $percentage / 100, 2);
    }

    /**
     * The application's security papers: its documents of any security
     * document type (LoanSecurityType::allDocumentTypes()).
     *
     * Scoped to the application, not to this one row: a document records which
     * loan application it was collected for, and there is no column that
     * records which of that application's securities it belongs to. So on a
     * loan pledged two properties this returns both properties' deeds, and the
     * caller can check that the papers a security needs are on file without
     * being able to tell the two properties' papers apart. Binding each
     * document to its own security is the security_documents association, which
     * is not built yet.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'loan_application_id', 'loan_application_id')
            ->where(function (Builder $query) {
                foreach (LoanSecurityType::allDocumentTypes() as $type) {
                    $query->orWhere(fn (Builder $one) => $one->ofDocumentType($type));
                }
            });
    }

    /** This security's own papers by type: hasDocument('property_deed'). */
    public function hasDocument(string $documentType): bool
    {
        return Document::query()
            ->ofDocumentType($documentType)
            ->where('loan_application_id', $this->loan_application_id)
            ->where('status', 'active')
            ->where('is_active', true)
            ->exists();
    }
}
