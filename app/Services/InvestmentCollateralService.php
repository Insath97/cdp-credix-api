<?php

namespace App\Services;

use App\Exceptions\InvestmentCollateralException;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use Carbon\Carbon;

/**
 * Investment-backed loans: a CDP Core investment policy pledged as security.
 *
 * Credix keeps only the policy number (loan_applications.collateral_policy_number).
 * Everything about the investment -- amount, plan, maturity, whose it is --
 * is read from CDP Core live, here, every time it matters. So a figure typed
 * into the wizard is never the figure the loan is secured on.
 *
 * Rules for a product with requires_investment_collateral (individual loans
 * only, one policy per loan):
 *   1. a policy number is given
 *   2. the policy belongs to the borrower (looked up by their NIC in Core)
 *   3. it is approved in Core (not pending, cancelled, terminated)
 *   4. no other LIVE Credix loan is secured by it
 *   5. requested_amount <= investment_amount x max_loan_percentage / 100
 *   6. investment maturity (reservation_date + duration) >= loan end date
 *
 * "Held" is derived from the loan's status: LoanApplication::holdingPolicy()
 * finds a live loan on the policy, and CDP Core asks that through
 * GET /external/core/policy-hold before it pays a policy out.
 */
class InvestmentCollateralService
{
    public function __construct(protected CdpConnectService $cdp)
    {
    }

    /**
     * The borrower's approved policies as CDP Core reports them, plus what
     * this product would lend against each and whether Credix already holds
     * it. This is what the wizard's picker shows; nothing here is stored.
     *
     * @return array{customer: ?array, summary: ?array, investments: array<int, array>}
     */
    public function investmentsFor(Customer $customer, ?LoanProduct $product = null): array
    {
        $payload = $this->fetch($customer);

        $investments = collect($this->items($payload))
            ->map(fn ($raw) => self::normalise((array) $raw))
            ->filter(fn ($inv) => $inv['eligible'] && $inv['policy_number'] !== '')
            ->map(function ($inv) use ($product) {
                $holder = LoanApplication::holdingPolicy($inv['policy_number'])
                    ->with('application')
                    ->first();

                $inv['max_loan']  = $product?->maxLoanAgainst((float) $inv['investment_amount']);
                $inv['in_use']    = $holder !== null;
                $inv['in_use_by'] = $holder?->reference();

                return $inv;
            })
            ->values()
            ->all();

        return [
            'customer'    => $payload['customer'] ?? null,
            'summary'     => $payload['summary'] ?? null,
            'investments' => $investments,
        ];
    }

    /**
     * Check one policy against the rules above and return it normalised.
     *
     * @throws InvestmentCollateralException
     */
    public function validate(
        LoanProduct $product,
        Customer $customer,
        ?string $policyNumber,
        float $requestedAmount,
        int $termMonths,
        ?Carbon $appliedAt = null,
        ?int $excludeLoanApplicationId = null
    ): ?array {
        if (!$product->requires_investment_collateral) {
            return null;
        }

        $policy = strtoupper(trim((string) $policyNumber));

        // Rule 1
        if ($policy === '') {
            throw new InvestmentCollateralException(
                "The {$product->name} product must be secured by a CDP Core investment. Select the customer's investment policy."
            );
        }

        // Rule 2 + 3: Core is asked for THIS customer's approved policies, so
        // anything not in the list is either not theirs or not approved.
        $inv = collect($this->investmentsFor($customer, $product)['investments'])
            ->first(fn ($i) => $i['policy_number'] === $policy);

        if (!$inv) {
            throw new InvestmentCollateralException(
                "Policy {$policy} was not found among {$this->who($customer)}'s approved investments in CDP Core."
            );
        }

        // Rule 4
        $holder = LoanApplication::holdingPolicy($policy)
            ->when($excludeLoanApplicationId, fn ($q) => $q->where('id', '!=', $excludeLoanApplicationId))
            ->with('application')
            ->first();

        if ($holder) {
            throw new InvestmentCollateralException(
                "Policy {$policy} already secures loan {$holder->reference()} (status: {$holder->status->value}). "
                . 'An investment can back only one loan at a time, until that loan is closed, rejected or cancelled.'
            );
        }

        // Rule 5
        $ceiling = $product->maxLoanAgainst((float) $inv['investment_amount']);
        if ($ceiling !== null && $requestedAmount > $ceiling) {
            throw new InvestmentCollateralException(
                sprintf(
                    'Requested amount Rs. %s exceeds %s%% of the investment value. The most this loan can be against policy %s (Rs. %s) is Rs. %s.',
                    number_format($requestedAmount, 2),
                    rtrim(rtrim((string) $product->max_loan_percentage, '0'), '.'),
                    $policy,
                    number_format((float) $inv['investment_amount'], 2),
                    number_format($ceiling, 2)
                ),
                'requested_amount'
            );
        }

        // Rule 6
        if (!empty($inv['maturity_date'])) {
            $start    = ($appliedAt ?? now())->copy()->startOfDay();
            $loanEnds = $start->copy()->addMonths($termMonths);
            $matures  = Carbon::parse($inv['maturity_date'])->startOfDay();

            if ($matures->lt($loanEnds)) {
                throw new InvestmentCollateralException(
                    sprintf(
                        'Policy %s matures on %s, before the loan would end on %s. Shorten the term to %d months or pledge a longer investment.',
                        $policy,
                        $matures->format('Y-m-d'),
                        $loanEnds->format('Y-m-d'),
                        max(1, $start->diffInMonths($matures))
                    ),
                    'term_months'
                );
            }
        }

        return $inv;
    }

    // ------------------------------------------------------------------

    /**
     * One shape for a CDP Core investment record (the item of
     * GET /api/v1/investments and /external/customer-investments):
     *
     *   policy_number                         (null until approved)
     *   investment_amount
     *   investment_product.name / .code / .roi_percentage
     *   reservation_date + duration_months -> maturity_date
     *   status === 'approved' && !cancelled/terminated/rejected -> eligible
     */
    public static function normalise(array $raw): array
    {
        $product = is_array($raw['investment_product'] ?? null) ? $raw['investment_product'] : [];

        $startedAt = $raw['reservation_date'] ?? null;
        $months    = (int) ($product['duration_months'] ?? 0);
        $maturity  = null;
        if ($startedAt && $months > 0) {
            try {
                $maturity = Carbon::parse($startedAt)->addMonths($months)->toDateString();
            } catch (\Throwable) {
                $maturity = null;
            }
        }

        $status   = strtolower(trim((string) ($raw['status'] ?? '')));
        $eligible = $status === 'approved'
            && empty($raw['cancelled_at'])
            && empty($raw['terminated_at'])
            && empty($raw['rejected_at'])
            && empty($raw['deleted_at']);

        return [
            'policy_number'      => strtoupper(trim((string) ($raw['policy_number'] ?? ''))),
            'application_number' => $raw['application_number'] ?? null,
            'investment_amount'  => round((float) ($raw['investment_amount'] ?? 0), 2),
            'product_name'       => $product['name'] ?? null,
            'product_code'       => $product['code'] ?? null,
            'roi_percentage'     => isset($product['roi_percentage']) ? (float) $product['roi_percentage'] : null,
            'duration_months'    => $months ?: null,
            'started_at'         => $startedAt ? Carbon::parse($startedAt)->toDateString() : null,
            'maturity_date'      => $maturity,
            'status'             => $status,
            'eligible'           => $eligible,
        ];
    }

    private function fetch(Customer $customer): array
    {
        $result = $this->cdp->getCustomerInvestments(
            (string) $customer->id_number,
            $this->cdpIdType($customer),
            'approved'
        );

        if (!$result['success']) {
            throw new InvestmentCollateralException(
                $result['message'],
                'collateral_policy_number',
                $result['status_code'] ?: 502
            );
        }

        return is_array($result['data'] ?? null) ? $result['data'] : [];
    }

    /** Core may hand back a plain list, {investments: [...]} or a paginator {data: [...]}. */
    private function items(array $payload): array
    {
        $items = $payload['investments'] ?? $payload['data'] ?? $payload;
        if (isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }

        return is_array($items) ? $items : [];
    }

    private function cdpIdType(Customer $customer): ?string
    {
        return match (strtolower(trim((string) $customer->id_type))) {
            'nic'             => 'nic',
            'passport'        => 'passport',
            'driving license', 'driving_license', 'driving licence' => 'driving_license',
            ''                => null,
            default           => 'other',
        };
    }

    private function who(Customer $customer): string
    {
        return trim((string) $customer->full_name) !== '' ? $customer->full_name : 'this customer';
    }
}
