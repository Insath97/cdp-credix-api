<?php

namespace App\Services;

use App\Enums\LoanSecurityType;
use App\Exceptions\SecurityLimitExceededException;
use App\Models\LendingMortgage;
use App\Traits\CalculatesMortgageAmount;

/**
 * What the securities pledged against a loan are worth, and therefore how much
 * that loan may be.
 *
 * Every security carries a value -- a CDP Investment by what CDP Core reports,
 * a Property Mortgage by its valuation, a Vehicle by its value -- and a plan
 * saying what percentage of that value this security may reach. The plan is
 * chosen per security from the type's configured plans, so two properties on
 * one loan can be lent under different plans (LoanSecurityPlanService):
 *
 *     eligible value of one security = value x percentage
 *     eligible value of the loan     = the sum over all its securities
 *
 * The officer sees each security's ceiling and the running total while filling
 * the step in (POST /loan-securities/coverage), and the same arithmetic refuses
 * the submission when the loan asks for more than the securities carry
 * (assertWithinLimit) -- a hard refusal, the way a CDP Investment has always
 * been capped by InvestmentCollateralService.
 *
 * The plan, percentage and ceiling are snapshotted onto each security row when
 * it is saved, so a later change to the configured plans cannot rewrite what a
 * reviewed loan was secured on. This service reads the live configuration;
 * LoanSecurityService is what writes the snapshot.
 */
class LoanSecurityLtvService
{
    use CalculatesMortgageAmount;

    public function __construct(protected LoanSecurityPlanService $plans)
    {
    }

    /**
     * What the securities have to be worth for this loan to go ahead: the
     * amount asked for, in other words.
     *
     * Deliberately its own method. A product may one day require the securities
     * to cover more than the amount borrowed, or a rate below 100% to lend
     * against a higher-risk mix; when that decision is made it belongs here,
     * and every caller reaches it through this one place rather than comparing
     * against the requested amount itself.
     */
    public function requiredSecurityValue(float $requestedAmount): float
    {
        return round($requestedAmount, 2);
    }

    /**
     * The percentage this security's plan lends at.
     *
     * Null when the plan is missing or is not one this type offers -- NOT a
     * type-wide fallback. A security that cannot say which plan it is under has
     * no percentage, so it is unmeasured: it contributes nothing to the coverage
     * and the refusal says so. Defaulting it to the type's old single
     * percentage would let it count towards the total at a rate nobody chose,
     * which is exactly the silent wrong number this design is meant to prevent.
     */
    public function maxLoanPercentage(LoanSecurityType $type, ?string $planCode): ?float
    {
        return $this->plans->percentageFor($type, $planCode);
    }

    /** 1,000,000 at 70% -> 700,000. Null when the plan cannot be resolved. */
    public function maxLoanAgainst(LoanSecurityType $type, float $value, ?string $planCode): ?float
    {
        $percentage = $this->maxLoanPercentage($type, $planCode);

        return $percentage === null ? null : $this->calculatePledgedAmount((float) $value, (float) $percentage);
    }

    /**
     * What this security is worth, from what has been entered for it. A CDP
     * Investment is not entered: its value is what CDP Core reported, which
     * LoanSecurityService puts in the row's investment_details.
     *
     * A type with no value given -- a property whose valuation has not been
     * entered -- reads as null rather than zero. That is a deliberate
     * difference: zero is a security worth nothing, and null is a security
     * whose value is not known yet. The form requires the value before the
     * loan may be submitted, so a null never survives to be counted.
     *
     * `value` in the payload wins over the type's own field. It is how a caller
     * passes a value it has already settled -- a stored row re-measured at
     * review, where the value lives in pledged_value rather than in the column
     * the form filled -- so that path does not have to pretend to be a form
     * submission and set every type's column at once.
     *
     * @param array<string, mixed>     $payload           the security as submitted
     * @param array<string, mixed>|null $investmentDetails what CDP Core reported, for a CDP Investment
     */
    public function valueFromPayload(
        LoanSecurityType $type,
        array $payload,
        ?array $investmentDetails = null
    ): ?float {
        if (array_key_exists('value', $payload)) {
            return is_numeric($payload['value']) ? round((float) $payload['value'], 2) : null;
        }

        if ($type === LoanSecurityType::CdpInvestment) {
            $amount = $investmentDetails['investment_amount'] ?? null;

            return is_numeric($amount) ? round((float) $amount, 2) : null;
        }

        $field = $type->valueField();
        $value = $field !== null ? ($payload[$field] ?? null) : null;

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    /**
     * The plan, percentage and ceiling to write onto the security row, so the
     * record shows what it was measured against rather than what the settings
     * happen to say today.
     *
     * The plan code travels with them. Re-measuring a stored loan at review can
     * then be held to the plan it was actually written under, rather than to
     * whichever plan of that type happens to lend at the most generous rate
     * today.
     *
     * @param  string|null $planCode null leaves both numbers null
     * @return array{security_plan: ?string, max_loan_percentage: ?float, max_loan_amount: ?float}
     */
    public function snapshot(LoanSecurityType $type, ?float $value, ?string $planCode): array
    {
        $percentage = $this->maxLoanPercentage($type, $planCode);

        return [
            'security_plan'       => $planCode !== null ? strtolower(trim($planCode)) : null,
            'max_loan_percentage' => $percentage,
            'max_loan_amount'     => ($percentage === null || $value === null)
                ? null
                : $this->calculatePledgedAmount((float) $value, (float) $percentage),
        ];
    }

    /**
     * The line the Loan Security step shows under the value box, so the officer
     * knows the limit before typing rather than being refused after:
     *
     *   Plan 1 (70%) -- Based on the property valuation, the maximum loan
     *   amount for this security is Rs. 7,000,000.00.
     *
     * With no plan chosen there is no limit to quote, and the line says that
     * rather than quoting a rate nobody selected.
     */
    public function helperText(LoanSecurityType $type, ?float $value, ?string $planCode = null): string
    {
        $plan = $this->plans->plan($type, $planCode);

        if (!$plan) {
            return sprintf(
                'Select a lending plan for this %s -- the limit is set by the plan, and no plan has been chosen.',
                $type->label()
            );
        }

        $percentage = self::formatPercentage($plan['percentage']);
        $limit      = sprintf('%s (%s%%)', $plan['label'], $percentage);

        if ($value === null) {
            return $limit . ' -- Enter the ' . $type->valueLabel()
                . ' to see the maximum loan amount for this security.';
        }

        return $limit . ' -- Based on the ' . $type->valueLabel()
            . ', the maximum loan amount for this security is Rs. '
            . number_format(round($value * $plan['percentage'] / 100, 2), 2) . '.';
    }

    /**
     * Every security's plan, value, percentage and ceiling -- what the frontend
     * needs to draw each security card's helper text, and what the refusal
     * message lists when the loan asks for too much.
     *
     * @param iterable<string|int, array<string, mixed>> $securities each with security_type,
     *                                                              security_plan and its value
     *                                                              (see valueFromPayload)
     * @return array<int, array<string, mixed>>
     */
    public function breakdown(iterable $securities): array
    {
        $rows = [];

        foreach ($securities as $index => $security) {
            $type = LoanSecurityType::from((string) ($security['security_type'] ?? ''));

            $value    = $this->valueFromPayload($type, $security, $security['investment_details'] ?? null);
            $planCode = $security['security_plan'] ?? null;

            $percentage = $security['max_loan_percentage'] ?? null;
            $planLabel  = null;

            if ($percentage === null && !empty($security['lending_mortgage_id'])) {
                $lendingMortgage = LendingMortgage::find($security['lending_mortgage_id']);
                if ($lendingMortgage) {
                    $percentage = (float) $lendingMortgage->percentage;
                    $planCode   = $lendingMortgage->code;
                    $planLabel  = $lendingMortgage->name;
                }
            }

            if ($percentage === null) {
                $plan       = $this->plans->plan($type, is_string($planCode) ? $planCode : null);
                $percentage = $plan['percentage'] ?? null;
                $planLabel  = $plan['label'] ?? null;
            }

            $rows[] = [
                'index'               => $index,
                'security_type'       => $type->value,
                'label'               => $type->label(),
                'security_plan'       => $planCode,
                'security_plan_label' => $planLabel,
                'security_plan_permission' => $planCode ? LoanSecurityPlanService::permissionFor((string) $planCode) : null,
                'value_field'         => $type->valueField(),
                'value_label'         => $type->valueLabel(),
                'value'               => $value,
                'max_loan_percentage' => $percentage === null ? null : self::formatPercentage($percentage),
                'max_loan_amount'     => ($percentage === null || $value === null)
                    ? null
                    : $this->calculatePledgedAmount((float) $value, (float) $percentage),
                'helper_text'         => $this->helperText($type, $value, is_string($planCode) ? $planCode : null),
            ];
        }

        return $rows;
    }

    /**
     * The loan's coverage: what the securities are worth between them, what
     * they can actually secure, what is left uncovered and whether that covers
     * what the loan needs. The frontend displays this rather than working it
     * out for itself.
     *
     * @param iterable<string|int, array<string, mixed>> $securities each with security_type and its value
     * @return array<string, mixed>
     */
    public function coverage(float $requestedAmount, iterable $securities): array
    {
        $breakdown = $this->breakdown($securities);
        $totals    = $this->totals($breakdown);

        $required = $this->requiredSecurityValue($requestedAmount);
        $eligible = $totals['total_max_loan_amount'];

        return [
            'requested_amount'             => round($requestedAmount, 2),
            'required_security_value'      => $required,
            'security_count'               => $totals['security_count'],
            'valued_security_count'        => $totals['valued_count'],
            'total_pledged_value'          => $totals['total_value'],
            'total_eligible_security_value' => $eligible,
            'remaining_uncovered_amount'   => round(max($required - $eligible, 0), 2),
            'coverage_percentage'          => $required > 0
                ? self::formatPercentage(round($eligible / $required * 100, 2))
                : 0.0,
            'is_sufficient'                => $eligible >= $required,
        ];
    }

    /**
     * The sums of a breakdown(). A security with no value yet contributes
     * nothing rather than blocking the total.
     *
     * @param iterable<int, array<string, mixed>> $breakdown
     * @return array{security_count: int, valued_count: int, total_value: float, total_max_loan_amount: float}
     */
    public function totals(iterable $breakdown): array
    {
        $breakdown = collect($breakdown);

        return [
            'security_count'       => $breakdown->count(),
            'valued_count'         => $breakdown->whereNotNull('value')->count(),
            'total_value'          => round((float) $breakdown->sum('value'), 2),
            'total_max_loan_amount' => round((float) $breakdown->sum('max_loan_amount'), 2),
        ];
    }

    /**
     * The refusal: the loan asks for more than its securities can carry.
     *
     * Checked on submit and again before review and verification, because by
     * then the requested amount, the valuations or the configured plans may all
     * have moved.
     *
     * @param iterable<string|int, array<string, mixed>> $securities each with security_type,
     *                                                              security_plan and its value
     *
     * @throws SecurityLimitExceededException
     */
    public function assertWithinLimit(float $requestedAmount, iterable $securities): void
    {
        $breakdown = $this->breakdown($securities);
        $totals    = $this->totals($breakdown);

        $required = $this->requiredSecurityValue($requestedAmount);
        $eligible = $totals['total_max_loan_amount'];

        if ($eligible >= $required) {
            return;
        }

        $totals['required_security_value']    = $required;
        $totals['remaining_uncovered_amount'] = round(max($required - $eligible, 0), 2);

        throw new SecurityLimitExceededException(
            $this->refusalMessage($requestedAmount, $breakdown, $totals),
            $breakdown,
            $totals
        );
    }

    /**
     * The refusal in one paragraph, naming each security's own plan and
     * arithmetic so the officer can see which card to change and by how much.
     *
     * A security with no usable plan is called out as such rather than left to
     * look like a security worth nothing -- otherwise a card whose plan was
     * never chosen reads as a zero and sends the officer to re-value an asset
     * that was valued correctly all along.
     *
     * @param array<int, array<string, mixed>> $breakdown
     * @param array<string, mixed>              $totals
     */
    private function refusalMessage(float $requestedAmount, array $breakdown, array $totals): string
    {
        $lines = [];

        foreach ($breakdown as $row) {
            if ($row['security_plan'] === null) {
                $lines[] = sprintf('%s: no lending plan chosen, so it secures nothing.', $row['label']);

                continue;
            }

            $lines[] = sprintf(
                '%s (%s at %s%%): %s = %s',
                $row['label'],
                $row['security_plan_label'] ?? $row['security_plan'],
                $row['max_loan_percentage'],
                $row['value'] === null ? 'no value entered' : 'Rs. ' . number_format($row['value'], 2),
                $row['max_loan_amount'] === null
                    ? 'not calculable'
                    : 'Rs. ' . number_format($row['max_loan_amount'], 2)
            );
        }

        $worth = number_format($totals['total_value'], 2);

        $subject = match (true) {
            $totals['security_count'] === 0 => 'No security carries a value.',
            $totals['security_count'] === 1 => "Its one security is worth Rs. {$worth}.",
            default => sprintf(
                'Its %d securities are worth Rs. %s between them.',
                $totals['security_count'],
                $worth
            ),
        };

        return sprintf(
            'Requested amount Rs. %s exceeds what the loan securities can carry. %s %s The most this loan can be is Rs. %s, so Rs. %s is still uncovered.',
            number_format($requestedAmount, 2),
            $subject,
            $lines === [] ? '' : implode('; ', $lines) . '.',
            number_format($totals['total_max_loan_amount'], 2),
            number_format($totals['remaining_uncovered_amount'], 2)
        );
    }

    /** 70 rather than 70.00 -- a percentage is not money. */
    public static function formatPercentage(float $percentage): float
    {
        return (float) rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.');
    }
}
