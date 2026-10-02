<?php

namespace App\Enums;

/**
 * What secures a Standard Borrowing loan (LoanProduct::requiresSecurity()) --
 * the asset the borrowers mortgage, Individual or Joint. The officer picks one
 * per application; its fields live on loan_application_securities and only
 * the chosen type's are accepted.
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
            self::PropertyMortgage => 'Property',
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
            self::Vehicle          => ['vehicle_make', 'vehicle_model', 'year_of_manufacture', 'year_of_registration',
                                       'registration_number', 'chassis_engine_number'],
        };
    }

    /**
     * The papers this security needs (Document::TYPES keys, uploaded through
     * POST /documents), each with whether it must be on file before the loan
     * can be reviewed or verified.
     *
     * @return array<string, bool>
     */
    public function documents(): array
    {
        return match ($this) {
            self::CdpInvestment    => ['investment_document' => true],
            self::PropertyMortgage => ['property_deed' => true, 'valuation_report' => false],
            self::Vehicle          => ['vehicle_cr' => true, 'valuation_report' => true],
        };
    }

    /** The main paper: the first of documents(). */
    public function documentType(): string
    {
        return array_key_first($this->documents());
    }

    /**
     * Every document type any security uses.
     *
     * @return array<int, string>
     */
    public static function allDocumentTypes(): array
    {
        return array_values(array_unique(array_merge(
            ...array_map(fn (self $type) => array_keys($type->documents()), self::cases())
        )));
    }
}
