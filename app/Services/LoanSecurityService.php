<?php

namespace App\Services;

use App\Enums\LoanSecurityType;
use App\Exceptions\InvestmentCollateralException;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanApplicationSecurity;
use App\Models\LoanProduct;
use Carbon\Carbon;

/**
 * Turns a validated `security` payload into the loan application's one
 * loan_application_securities row. Shared by create and update.
 *
 * The FormRequest has already checked the per-type fields. What is left is
 * what only a live system can answer: for a CDP Investment, whether CDP Core
 * agrees the policy may secure this loan (InvestmentCollateralService).
 */
class LoanSecurityService
{
    public function __construct(protected InvestmentCollateralService $investmentCollateral)
    {
    }

    /**
     * The row to write for this security: the chosen type's fields, every
     * other type's columns cleared, and for a CDP Investment the policy as
     * CDP Core reports it.
     *
     * Calls CDP Core for a CDP Investment, so run it before opening a
     * transaction; assertPolicyStillFree() repeats the one check that can
     * race, under the lock.
     *
     * @throws InvestmentCollateralException
     */
    public function prepare(
        array $security,
        LoanProduct $product,
        Customer $borrower,
        float $requestedAmount,
        int $termMonths,
        ?Carbon $appliedAt = null,
        ?int $excludeLoanApplicationId = null
    ): array {
        $type = LoanSecurityType::from($security['security_type']);

        $row = ['security_type' => $type->value, 'investment_details' => null];

        foreach (LoanSecurityType::cases() as $each) {
            foreach ($each->fields() as $field) {
                $row[$field] = $each === $type ? ($security[$field] ?? null) : null;
            }
        }

        if ($type === LoanSecurityType::CdpInvestment) {
            $investment = $this->investmentCollateral->validate(
                $product,
                $borrower,
                $security['investment_nic'] ?? null,
                $security['policy_number'] ?? null,
                $requestedAmount,
                $termMonths,
                $appliedAt,
                $excludeLoanApplicationId
            );

            $row['investment_nic'] = CustomerLoanEligibilityService::normalizeNic($security['investment_nic'] ?? null);
            $row['policy_number']  = $investment['policy_number'];

            // What Core said on the day, for the reviewers. The lookup flags
            // are left out: they describe the search, not the investment.
            $row['investment_details'] = collect($investment)
                ->except(['in_use', 'in_use_by', 'eligible'])
                ->put('retrieved_at', now()->toDateTimeString())
                ->all();
        }

        return $row;
    }

    /**
     * The security as it stands, in the payload shape prepare() takes -- for
     * re-checking an unchanged CDP Investment when the loan's borrower,
     * product, amount, term or start date is edited.
     */
    public function payloadFrom(LoanApplicationSecurity $security): array
    {
        $payload = ['security_type' => $security->security_type->value];

        foreach ($security->security_type->fields() as $field) {
            $payload[$field] = $security->getAttributes()[$field] ?? null;
        }

        return $payload;
    }

    /**
     * A CDP policy may secure only one live loan. prepare() checked that
     * before the transaction; this repeats it under a row lock, so two
     * submissions racing for the same policy cannot both get through.
     *
     * @throws InvestmentCollateralException
     */
    public function assertPolicyStillFree(array $row, ?int $excludeLoanApplicationId = null): void
    {
        if (($row['security_type'] ?? null) !== LoanSecurityType::CdpInvestment->value || empty($row['policy_number'])) {
            return;
        }

        $holder = LoanApplication::holdingPolicy($row['policy_number'])
            ->when($excludeLoanApplicationId, fn ($q) => $q->where('id', '!=', $excludeLoanApplicationId))
            ->lockForUpdate()
            ->with('application')
            ->first();

        if ($holder) {
            throw new InvestmentCollateralException(
                "Policy {$row['policy_number']} was pledged against loan {$holder->reference()} a moment ago."
            );
        }
    }

    /** Write the application's one security row, replacing any it had. */
    public function save(LoanApplication $loanApplication, array $row): LoanApplicationSecurity
    {
        return LoanApplicationSecurity::updateOrCreate(
            ['loan_application_id' => $loanApplication->id],
            $row
        );
    }
}
