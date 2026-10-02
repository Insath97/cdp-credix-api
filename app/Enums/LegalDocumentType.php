<?php

namespace App\Enums;

/**
 * The agreements the legal desk draws up. Each loan product lists the ones
 * its loans need (loan_product_legal_documents), so adding an agreement means
 * a case here and a value in that table's enum column.
 */
enum LegalDocumentType: string
{
    case DirectLoanAgreement = 'direct_loan_agreement';
    case LoanAgreementInvestment = 'loan_agreement_investment';
    case LoanApplicationAcknowledgement = 'loan_application_acknowledgement';

    public function label(): string
    {
        return match ($this) {
            self::DirectLoanAgreement            => 'Direct Loan Agreement With Two Guarantors',
            self::LoanAgreementInvestment        => 'Loan Agreement Against Investment',
            self::LoanApplicationAcknowledgement => 'Loan Application & Acknowledgement Against Investment',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return array_combine(self::values(), array_map(fn (self $type) => $type->label(), self::cases()));
    }

    /** The label of a stored value; an unknown value is shown as it is. */
    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? (string) $value;
    }
}
