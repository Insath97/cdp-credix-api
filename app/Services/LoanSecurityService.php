<?php

namespace App\Services;

use App\Enums\LoanSecurityType;
use App\Exceptions\InvestmentCollateralException;
use App\Models\Customer;
use App\Models\LendingMortgage;
use App\Models\LoanApplication;
use App\Models\LoanApplicationSecurity;
use App\Traits\CalculatesMortgageAmount;
use Carbon\Carbon;

/**
 * Turns a validated `securities` payload into the loan application's
 * loan_application_securities rows. Shared by create and update.
 *
 * The FormRequest has already checked each item's fields. What is left is what
 * only a live system can answer: whether the lending plan named for each
 * security is one this type offers and one the person making the application is
 * permitted to use (LoanSecurityPlanService); for a CDP Investment, whether CDP
 * Core agrees the policy may secure this loan (InvestmentCollateralService); and
 * for every security, what it is worth under its plan and what that is worth as a
 * loan (LoanSecurityLtvService).
 *
 * One loan may carry several securities, each under its own plan, so everything
 * here works on the collection rather than on a single security.
 */
class LoanSecurityService
{
    use CalculatesMortgageAmount;
    public function __construct(
        protected InvestmentCollateralService $investmentCollateral,
        protected LoanSecurityLtvService $ltv,
        protected LoanSecurityPlanService $planService
    ) {
    }

    /**
     * The rows to write for this application: one per security, each with its
     * own fields filled, every other type's columns cleared, its value settled,
     * and the percentage and ceiling that value earns snapshotted onto it.
     *
     * Calls CDP Core for a CDP Investment, so run it before opening a
     * transaction; assertPoliciesStillFree() repeats the one check that can
     * race, under the lock.
     *
     * @param array<int, array<string, mixed>> $securities
     * @param iterable<int, Customer>          $borrowers  everyone borrowing on the loan, primary first;
     *                                                    a CDP Investment must belong to one of them
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws InvestmentCollateralException
     * @throws \App\Exceptions\SecurityLimitExceededException
     */
    public function prepareAll(
        array $securities,
        iterable $borrowers,
        float $requestedAmount,
        int $termMonths,
        ?Carbon $appliedAt = null,
        ?int $excludeLoanApplicationId = null
    ): array {
        $rows = [];

        // Whether the loan carries a security besides this one. A CDP
        // Investment that has to secure the loan on its own is held to the
        // cdp_investment_max_loan_percentage ceiling by itself; one that sits
        // beside a property or a second investment is counted toward the
        // aggregate instead, which assertWithinLimit() measures just below.
        $securedByOthers = count($securities) > 1;

        foreach (array_values($securities) as $security) {
            $rows[] = $this->prepare(
                $security,
                $borrowers,
                $requestedAmount,
                $termMonths,
                $appliedAt,
                $excludeLoanApplicationId,
                $securedByOthers
            );
        }

        $this->ltv->assertWithinLimit($requestedAmount, $rows);

        return $rows;
    }

    /**
     * The row for one security: the chosen type's fields, every other type's
     * columns cleared, its lending plan and the value and ceiling that plan
     * earns, and for a CDP Investment the policy as CDP Core reports it.
     *
     * The plan is resolved here, and it is resolved twice over: the plan has to
     * exist for this type, and the person making the application has to hold its
     * permission. Both are refusals at this point rather than corrections, so
     * that a plan nobody is allowed to use never reaches the database to be
     * discovered at review.
     *
     * @param array<string, mixed>  $security
     * @param iterable<int, Customer> $borrowers
     * @param bool $securedByOthers whether another security is pledged alongside this one
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\SecurityPlanException
     * @throws InvestmentCollateralException
     */
    public function prepare(
        array $security,
        iterable $borrowers,
        float $requestedAmount,
        int $termMonths,
        ?Carbon $appliedAt = null,
        ?int $excludeLoanApplicationId = null,
        bool $securedByOthers = false
    ): array {
        $type = LoanSecurityType::from($security['security_type']);

        $lendingMortgage = null;
        if (!empty($security['lending_mortgage_id'])) {
            $lendingMortgage = LendingMortgage::find($security['lending_mortgage_id']);
        }

        if ($lendingMortgage) {
            $planCode          = $lendingMortgage->code;
            $percentage        = (float) $lendingMortgage->percentage;
            $lendingMortgageId = $lendingMortgage->id;
        } else {
            $plan              = $this->planService->assertUsable($type, $security['security_plan'] ?? null);
            $planCode          = $plan['code'];
            $percentage        = (float) $plan['percentage'];
            $lendingMortgageId = null;
        }

        $row = [
            'security_type'       => $type->value,
            'security_plan'       => $planCode,
            'lending_mortgage_id' => $lendingMortgageId,
            'investment_details'  => null,
        ];

        if ($type === LoanSecurityType::PropertyMortgage) {
            $security['owner_name'] = $security['owner_name'] ?? $security['property_owner'] ?? null;
            $security['owner_deed_number'] = $security['owner_deed_number'] ?? $security['deed_number'] ?? $security['title_number'] ?? $security['deed_title_number'] ?? null;
            $security['estimated_value'] = $security['estimated_value'] ?? $security['market_value'] ?? $security['estimated_market_value'] ?? null;
            $security['evaluation_date'] = $security['evaluation_date'] ?? $security['valuation_date'] ?? null;
            $security['evaluated_by'] = $security['evaluated_by'] ?? $security['assessed_by'] ?? $security['evaluated_assessed_by'] ?? null;
            $security['evaluation_remarks'] = $security['evaluation_remarks'] ?? $security['valuation_notes'] ?? $security['land_description'] ?? null;
        }

        foreach (LoanSecurityType::cases() as $each) {
            foreach ($each->fields() as $field) {
                $row[$field] = $each === $type ? ($security[$field] ?? null) : null;
            }
        }

        if ($type === LoanSecurityType::CdpInvestment) {
            $investment = $this->investmentCollateral->validate(
                $borrowers,
                $security['investment_nic'] ?? null,
                $security['policy_number'] ?? null,
                $requestedAmount,
                $termMonths,
                $appliedAt,
                $excludeLoanApplicationId,
                securedByOthers: $securedByOthers,
                maxLoanPercentage: $percentage
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

        // What this security is worth, settled once here and written on the row
        // so nothing downstream has to work it out again. For a CDP Investment
        // that is Core's figure, not one the request supplied.
        $value = $this->ltv->valueFromPayload($type, $security, $row['investment_details']);

        $row['pledged_value']        = $value;
        $row['max_loan_percentage']  = $percentage;
        $row['max_loan_amount']      = $value !== null ? $this->calculatePledgedAmount((float) $value, (float) $percentage) : null;

        return $row;
    }

    /**
     * The securities as they stand, in the payload shape prepare() takes -- for
     * re-checking unchanged CDP Investments when the loan's borrower, product,
     * amount, term or start date is edited.
     *
     * The stored plan travels with the payload. Re-preparing an unchanged
     * security must not quietly move it onto whatever plan lends at the most
     * generous rate today, and it must not demand a permission the person
     * editing some other field of the loan may not hold.
     *
     * @return array<int, array<string, mixed>>
     */
    public function payloadsFrom(iterable $securities): array
    {
        $payloads = [];

        foreach ($securities as $security) {
            $payload = [
                'security_type'       => $security->security_type->value,
                'security_plan'       => $security->security_plan,
                'lending_mortgage_id' => $security->lending_mortgage_id,
            ];

            foreach ($security->security_type->fields() as $field) {
                $payload[$field] = $security->getAttributes()[$field] ?? null;
            }

            $payloads[] = $payload;
        }

        return $payloads;
    }

    /**
     * A CDP policy may secure only one live loan. prepare() checked that
     * before the transaction; this repeats it under a row lock, so two
     * submissions racing for the same policy cannot both get through. Run for
     * every security on the loan, not just the first: two entries in one
     * payload could name the same policy, and so could two officers.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @throws InvestmentCollateralException
     */
    public function assertPoliciesStillFree(array $rows, ?int $excludeLoanApplicationId = null): void
    {
        $seen = [];

        foreach ($rows as $row) {
            if (($row['security_type'] ?? null) !== LoanSecurityType::CdpInvestment->value || empty($row['policy_number'])) {
                continue;
            }

            $policy = InvestmentCollateralService::normalisePolicy($row['policy_number']);

            // The same policy named twice in one payload would otherwise be
            // checked against the database twice and found free both times,
            // because neither row is written until later.
            if (isset($seen[$policy])) {
                throw new InvestmentCollateralException(
                    "Policy {$policy} is pledged twice on the same loan application. Each investment can secure a loan once.",
                    'securities'
                );
            }
            $seen[$policy] = true;

            $holder = LoanApplication::holdingPolicy($policy)
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
    }

    /**
     * Write the application's securities, replacing the ones it had.
     *
     * Replaced rather than merged: `securities` sent on a request is the whole
     * list, so removing a security from the payload removes it from the loan.
     * That is the same rule the single-security version worked under, and it is
     * why the workflow refuses a change to the securities after approval --
     * by then the list is part of what was approved.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return \Illuminate\Support\Collection<int, LoanApplicationSecurity>
     */
    public function saveCollection(LoanApplication $loanApplication, array $rows)
    {
        $loanApplication->securities()->delete();

        $saved = collect();

        foreach ($rows as $row) {
            $saved->push(LoanApplicationSecurity::create($row + [
                'loan_application_id' => $loanApplication->id,
            ]));
        }

        return $saved;
    }
}
