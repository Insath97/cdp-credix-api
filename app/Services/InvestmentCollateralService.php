<?php

namespace App\Services;

use App\Exceptions\InvestmentCollateralException;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use Carbon\Carbon;

/**
 * CDP Investment as a loan security: a CDP Core investment policy pledged
 * against a loan on a secured product (loan_products.requires_security).
 *
 * The officer types the NIC and the policy number into the Loan Security
 * step. Credix keeps those on loan_application_securities, plus a snapshot of
 * what CDP Core reported at that moment (investment_details) for reviewers to
 * read. Every rule below is checked against Core live, so a figure typed into
 * the wizard is never the figure the loan is secured on.
 *
 * Rules for a CDP Investment security (individual loans only, one policy per
 * loan):
 *   1. a policy number is given
 *   2. the NIC given is the borrower's own (old and new NIC formats match)
 *   3. the policy is approved in Core under that NIC (not pending, cancelled, terminated)
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
     * it. This is what the customer investment picker shows; nothing here is
     * stored.
     *
     * @return array{customer: ?array, summary: ?array, investments: array<int, array>}
     */
    public function investmentsFor(Customer $customer, ?LoanProduct $product = null): array
    {
        return $this->approvedInvestments(
            (string) $customer->id_number,
            $this->cdpIdType($customer),
            $product,
            'id_number'
        );
    }

    /**
     * One policy, by the NIC and policy number typed into the Loan Security
     * step -- what the wizard displays before the application is submitted.
     * Nothing is stored. Given the borrower, rule 2 is checked here as well,
     * so a wrong NIC is caught before the officer reaches the end.
     *
     * @return array{customer: ?array, investment: array}
     *
     * @throws InvestmentCollateralException
     */
    public function lookup(
        string $nic,
        string $policyNumber,
        ?LoanProduct $product = null,
        ?Customer $borrower = null,
        ?int $excludeLoanApplicationId = null
    ): array {
        $policy = self::normalisePolicy($policyNumber);

        if ($borrower) {
            $this->assertBorrowersNic($borrower, $nic, 'nic');
        }

        $result = $this->approvedInvestments(
            CustomerLoanEligibilityService::normalizeNic($nic),
            $borrower ? $this->cdpIdType($borrower) : 'nic',
            $product,
            'nic',
            $excludeLoanApplicationId
        );

        $inv = collect($result['investments'])->firstWhere('policy_number', $policy);

        if (!$inv) {
            throw new InvestmentCollateralException(
                "Policy {$policy} was not found among the approved CDP Core investments for NIC " . trim($nic) . '.',
                'policy_number'
            );
        }

        return [
            'customer'   => $result['customer'],
            'investment' => $inv,
        ];
    }

    /**
     * Check a CDP Investment security against the rules above and return the
     * investment as Core reports it -- the caller keeps it as the security's
     * investment_details.
     *
     * @throws InvestmentCollateralException
     */
    public function validate(
        LoanProduct $product,
        Customer $customer,
        ?string $nic,
        ?string $policyNumber,
        float $requestedAmount,
        int $termMonths,
        ?Carbon $appliedAt = null,
        ?int $excludeLoanApplicationId = null
    ): array {
        $policy = self::normalisePolicy($policyNumber);

        // Rule 1
        if ($policy === '') {
            throw new InvestmentCollateralException(
                'Enter the investment policy number that secures this loan.'
            );
        }

        // Rule 2
        $this->assertBorrowersNic($customer, $nic, 'security.investment_nic');

        // Rule 3: Core is asked for THIS NIC's approved policies, so anything
        // not in the list is either not theirs or not approved.
        $inv = collect($this->approvedInvestments(
            CustomerLoanEligibilityService::normalizeNic($nic),
            $this->cdpIdType($customer),
            $product,
            'security.investment_nic'
        )['investments'])->firstWhere('policy_number', $policy);

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

    /** Trimmed and upper-cased: the one form policy numbers are compared in. */
    public static function normalisePolicy(?string $policyNumber): string
    {
        return strtoupper(trim((string) $policyNumber));
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
            'policy_number'      => self::normalisePolicy($raw['policy_number'] ?? ''),
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

    /**
     * Rule 2: a CDP Investment secures only its owner's own loan. The NIC is
     * compared in every spelling, so 853400937V and 198534000937 match.
     *
     * @throws InvestmentCollateralException
     */
    private function assertBorrowersNic(Customer $customer, ?string $nic, string $field): void
    {
        $variants = CustomerLoanEligibilityService::nicVariants($customer->id_number);

        if (!in_array(CustomerLoanEligibilityService::normalizeNic($nic), $variants, true)) {
            throw new InvestmentCollateralException(
                sprintf(
                    "NIC %s is not the NIC of %s (%s). A CDP Investment can only secure its owner's own loan.",
                    trim((string) $nic),
                    $this->who($customer),
                    trim((string) $customer->id_number) !== '' ? trim((string) $customer->id_number) : 'no ID number on record'
                ),
                $field
            );
        }
    }

    /**
     * The approved, pledgeable policies Core holds under one ID number, each
     * with what the product would lend against it and whether a live Credix
     * loan already holds it.
     *
     * @return array{customer: ?array, summary: ?array, investments: array<int, array>}
     */
    private function approvedInvestments(
        string $idNumber,
        ?string $idType,
        ?LoanProduct $product,
        string $field,
        ?int $excludeLoanApplicationId = null
    ): array {
        $payload = $this->fetch($idNumber, $idType, $field);

        $investments = collect($this->items($payload))
            ->map(fn ($raw) => self::normalise((array) $raw))
            ->filter(fn ($inv) => $inv['eligible'] && $inv['policy_number'] !== '')
            ->map(function ($inv) use ($product, $excludeLoanApplicationId) {
                $holder = LoanApplication::holdingPolicy($inv['policy_number'])
                    ->when($excludeLoanApplicationId, fn ($q) => $q->where('id', '!=', $excludeLoanApplicationId))
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

    private function fetch(string $idNumber, ?string $idType, string $field): array
    {
        $result = $this->cdp->getCustomerInvestments($idNumber, $idType, 'approved');

        if (!$result['success']) {
            $code = (int) $result['status_code'];

            // Core's status is not handed back as-is. Its 401 means Credix's
            // own API key was refused; passed through, the frontend reads it
            // as the officer's session having expired and signs them out.
            $status = match ($code) {
                404, 422 => 422,
                503      => 503,
                default  => 502,
            };

            $message = $code === 404
                ? 'No CDP Core investments were found for ' . trim($idNumber) . '.'
                : $result['message'];

            throw new InvestmentCollateralException($message, $field, $status);
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
