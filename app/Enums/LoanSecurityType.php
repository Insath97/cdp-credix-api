<?php

namespace App\Enums;

/**
 * What secures a loan on a product with requires_security (e.g. Mortgage Loan).
 * The officer picks one per application; its fields live on
 * loan_application_securities and only the chosen type's are accepted.
 */
enum LoanSecurityType: string
{
    case CdpInvestment = 'cdp_investment';
    case PropertyMortgage = 'property_mortgage';
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return match ($this) {
            self::CdpInvestment    => 'CDP Investment',
            self::PropertyMortgage => 'Property Mortgage',
            self::Vehicle          => 'Vehicle',
        };
    }

    /**
     * The loan_application_securities columns this type fills from the form.
     * Every other type's columns are refused by validation and saved as null.
     *
     * @return array<int, string>
     */
    public function fields(): array
    {
        return match ($this) {
            self::CdpInvestment    => ['investment_nic', 'policy_number'],
            self::PropertyMortgage => ['owner_name', 'property_type', 'owner_deed_number',
                                       'estimated_value', 'evaluation_date', 'evaluated_by', 'evaluation_remarks'],
            self::Vehicle          => ['vehicle_make', 'vehicle_model', 'year_of_manufacture', 'year_of_registration'],
        };
    }

    /**
     * The supporting document (a Document::TYPES key) uploaded through
     * POST /documents against the loan application.
     */
    public function documentType(): string
    {
        return match ($this) {
            self::CdpInvestment    => 'investment_document',
            self::PropertyMortgage => 'property_deed',
            self::Vehicle          => 'vehicle_cr',
        };
    }

    /**
     * Whether that document must be on file before the loan can be reviewed.
     * Only the Vehicle CR is; the investment document and the deed are
     * optional supporting papers.
     */
    public function documentRequired(): bool
    {
        return $this === self::Vehicle;
    }
}
