<?php

namespace App\Models;

use App\Enums\LoanSecurityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The security pledged against one loan application -- a CDP Investment, a
 * Property Mortgage or a Vehicle. Required on every Standard Borrowing loan
 * under the General term, Individual or Joint (LoanProduct::requiresSecurity()).
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
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'loan_application_id'  => 'integer',
        'security_type'        => LoanSecurityType::class,
        'investment_details'   => 'array',
        'estimated_value'      => 'decimal:2',
        // date:Y-m-d, not date -- a plain date cast serialises in UTC and the
        // frontend shows the day before (APP_TIMEZONE is Asia/Colombo).
        'evaluation_date'      => 'date:Y-m-d',
        'year_of_manufacture'  => 'integer',
        'year_of_registration' => 'integer',
    ];

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * The application's security papers: its documents of any security
     * document type (LoanSecurityType::allDocumentTypes()), not just this
     * security's own, so the relation behaves the same whether it is
     * eager-loaded or not.
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
}
