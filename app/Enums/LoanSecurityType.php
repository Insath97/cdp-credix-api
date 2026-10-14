<?php

namespace App\Enums;

/**
 * What secures a Standard Borrowing loan (LoanProduct::requiresSecurity()) --
 * the asset the borrowers mortgage, Individual or Joint. The officer picks one
 * or more per application; each one's fields live on its own
 * loan_application_securities row and only that type's are accepted.
 *
 * Every type is valued, and the percentage of that value a loan may reach is not
 * the type's own but one of its PLANS' -- LoanSecurityPlanService reads them
 * from the System Settings table as {type}_security_plans. A CDP Investment is
 * valued by CDP Core rather than by the officer, so it has no value field; a
 * property and a vehicle have one.
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

    public function lendingMortgageType(): string
    {
        return match ($this) {
            self::CdpInvestment    => 'CDP investment',
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
                                       'registration_number', 'chassis_engine_number', 'vehicle_value'],
        };
    }

    /**
     * The column this type's value is entered in, or null when the officer
     * does not enter one: a CDP Investment is worth what CDP Core says it is
     * worth, and taking a number for it from the form would only let the
     * frontend invent the collateral.
     */
    public function valueField(): ?string
    {
        return match ($this) {
            self::CdpInvestment    => null,
            self::PropertyMortgage => 'estimated_value',
            self::Vehicle          => 'vehicle_value',
        };
    }

    /** What that value is called on the form, for messages and the frontend. */
    public function valueLabel(): string
    {
        return match ($this) {
            self::CdpInvestment    => 'investment value',
            self::PropertyMortgage => 'property valuation',
            self::Vehicle          => 'vehicle value',
        };
    }

/**
     * The System Setting row holding this type's lending plans, as one JSON value.
     *
     * @see \App\Services\LoanSecurityPlanService::settingKey()
     */
    public function plansSetting(): string
    {
        return $this->value . '_security_plans';
    }

    /**
     * The pre-plan percentage setting, kept only to seed Plan 1 from and to read
     * on an installation whose plans have not been configured. Nothing in the
     * live path falls back to it once a plan exists: a type-wide rate would be
     * the rate nobody chose.
     *
     * @see \App\Services\LoanSecurityPlanService
     */
    public function maxLoanPercentageSetting(): string
    {
        return $this->value . '_max_loan_percentage';
    }

    /**
     * What Plan 1 is seeded at on an installation with no pre-plan setting row:
     * 70 for a property and a vehicle, 80 for a CDP Investment.
     *
     * The CDP Investment's 80 is the long-standing fallback and is deliberately
     * not changed here -- tightening or loosening it is a business decision, and
     * the seeded row (50) and this fallback have never agreed.
     */
    public function defaultMaxLoanPercentage(): float
    {
        return match ($this) {
            self::CdpInvestment    => 80.0,
            self::PropertyMortgage => 70.0,
            self::Vehicle          => 70.0,
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
