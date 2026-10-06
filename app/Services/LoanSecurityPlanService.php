<?php

namespace App\Services;

use App\Enums\LoanSecurityType;
use App\Exceptions\SecurityPlanException;
use App\Models\Setting;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The Security Type -> Plan -> Percentage -> Permission hierarchy.
 *
 * A security type does not carry a percentage of its own any more. It carries a
 * list of PLANS, each with its own percentage and its own permission, and the
 * officer picks the plan they are lending under. That is what lets one type lend
 * at 70% for a straightforward property and 50% for a riskier one without a
 * code change.
 *
 * The plans live in the System Settings table as one JSON row per security type
 * ({type}_security_plans), not in a table of their own:
 *
 *     {
 *       "plan_1": {"label": "Plan 1", "percentage": 70},
 *       "plan_2": {"label": "Plan 2", "percentage": 60}
 *     }
 *
 * A plan's code doubles as its permission: plan_1 is `use_plan_1`. So adding a
 * plan means adding a setting entry and granting a permission -- no migration.
 *
 * The permission is deliberately keyed on the plan and NOT on the type, so
 * `use_plan_1` is one grant the same person holds for every type. The
 * consequence, which is intended: someone permitted to use Plan 1 may use
 * Plan 1 of every security type, CDP Investment included.
 *
 * Permission is checked when a security is SUBMITTED, never when a stored one
 * is measured. A reviewer who lacks the permission still measures and judges the
 * loans they are reviewing, and a permission withdrawn later cannot make an
 * existing application suddenly unreadable.
 */
class LoanSecurityPlanService
{
    /** The System Settings row holding one security type's plans. */
    public static function settingKey(LoanSecurityType $type): string
    {
        return $type->value . '_security_plans';
    }

    /**
     * The plans configured for one security type, in the order they were written.
     *
     * Entries that cannot be used -- not an array, no usable code, a percentage
     * that is not a number -- are left out rather than surfaced. A plan whose
     * percentage is missing or outside 0..100 is dropped, because a percentage
     * above 100 would lend more than the security is worth and a missing one
     * cannot be measured at all; a plan that reads as unusable should not be
     * offered and then produce a wrong ceiling.
     *
     * @return array<int, array{code: string, label: string, percentage: float}>
     */
    public function plans(LoanSecurityType $type): array
    {
        $plans = [];

        try {
            $dbMortgages = \App\Models\LendingMortgage::where('type', $type->lendingMortgageType())
                ->where('status', \App\Models\LendingMortgage::STATUS_ACTIVE)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($dbMortgages as $index => $row) {
                $normalisedCode = $this->normaliseCode((string) $row->code);
                $percentage = (float) $row->percentage;

                if ($normalisedCode === '' || $percentage < 0 || $percentage > 100) {
                    continue;
                }

                $planNum = $index + 1;
                $plans[] = [
                    'code'       => 'plan_' . $planNum,
                    'db_code'    => (string) $row->code,
                    'name'       => (string) ($row->name ?? ''),
                    'label'      => !empty($row->name) ? (string) $row->name : ('Plan ' . $planNum),
                    'percentage' => $percentage,
                ];
            }
        } catch (\Throwable $e) {
            // Table may not exist in early migration phases
        }

        if (empty($plans)) {
            $configured = Setting::get(self::settingKey($type), []);

            if (is_array($configured)) {
                foreach ($configured as $code => $plan) {
                    $code = $this->normaliseCode((string) $code);

                    if ($code === '' || !is_array($plan)) {
                        continue;
                    }

                    $percentage = $plan['percentage'] ?? null;

                    if (!is_numeric($percentage) || (float) $percentage < 0 || (float) $percentage > 100) {
                        continue;
                    }

                    $plans[] = [
                        'code'       => $code,
                        'label'      => trim((string) ($plan['label'] ?? '')) ?: $this->defaultLabel($code),
                        'percentage' => (float) $percentage,
                    ];
                }
            }
        }

        return $plans;
    }

    /**
     * One plan of one security type, or null when the code names no plan that
     * type actually offers. Null rather than an exception, because the caller
     * decides whether an unknown plan is a validation error or simply an
     * unmeasured security.
     *
     * @return array{code: string, label: string, percentage: float}|null
     */
    public function plan(LoanSecurityType $type, ?string $code): ?array
    {
        $code = $this->normaliseCode((string) $code);

        if ($code === '') {
            return null;
        }

        $allPlans = $this->plans($type);

        foreach ($allPlans as $plan) {
            if ($plan['code'] === $code || (isset($plan['db_code']) && $this->normaliseCode($plan['db_code']) === $code)) {
                return $plan;
            }
        }

        if (str_starts_with($code, 'plan_')) {
            $index = (int) str_replace('plan_', '', $code) - 1;
            if (isset($allPlans[$index])) {
                return $allPlans[$index];
            }
        }

        if (str_contains($code, '001')) {
            return $allPlans[0] ?? null;
        }
        if (str_contains($code, '002')) {
            return $allPlans[1] ?? null;
        }
        if (str_contains($code, '003')) {
            return $allPlans[2] ?? null;
        }

        return null;
    }

    /** The permission a plan is gated on: plan_1 -> use_plan_1, CDP-INV-001 -> use_plan_1. */
    public static function permissionFor(string $planCode): string
    {
        $normalized = self::normaliseCode($planCode);
        if (str_starts_with($normalized, 'plan_')) {
            return 'use_' . $normalized;
        }
        if (str_ends_with($normalized, '001') || str_ends_with($normalized, '1')) {
            return 'use_plan_1';
        }
        if (str_ends_with($normalized, '002') || str_ends_with($normalized, '2')) {
            return 'use_plan_2';
        }
        if (str_ends_with($normalized, '003') || str_ends_with($normalized, '3')) {
            return 'use_plan_3';
        }
        return 'use_' . str_replace('-', '_', $normalized);
    }

    /**
     * Whether the given user may lend under this plan.
     *
     * False when nobody is signed in. Failing closed matters here: an
     * unauthenticated call reaching the submission path means the route's own
     * gate has already let something through, and the safe answer to "may this
     * person use the most generous plan" is still no.
     */
    public function mayUse(string $planCode, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        $normalized = self::normaliseCode($planCode);
        $primaryPerm = self::permissionFor($planCode);
        $directPerm = 'use_' . str_replace('-', '_', $normalized);

        return $user->can($primaryPerm) || $user->can($directPerm);
    }

    /**
     * The plans this user is allowed to use for one type, each flagged. The
     * options and coverage endpoints return this so the frontend shows only the
     * plans the officer may actually pick -- while the refusal stays on the
     * server, because a hidden option is not a rule.
     *
     * @return array<int, array{code: string, label: string, percentage: float, allowed: bool, permission: string}>
     */
    public function plansFor(LoanSecurityType $type, ?Authenticatable $user = null): array
    {
        $user ??= auth()->user();

        return array_map(function (array $plan) use ($user): array {
            return $plan + [
                'allowed'    => $this->mayUse($plan['code'], $user),
                'permission' => self::permissionFor($plan['code']),
            ];
        }, $this->plans($type));
    }

    /**
     * The plan a security is being written under, or an explanation of why it
     * cannot be written.
     *
     * A plan is required and there is no fallback to a type-wide percentage. A
     * security without one cannot be measured, and a security that cannot be
     * measured must not be allowed to sit silently in the coverage total --
     * which is what a defaulted percentage would have done, invisibly.
     *
     * @return array{code: string, label: string, percentage: float}
     *
     * @throws SecurityPlanException
     */
    public function assertUsable(
        LoanSecurityType $type,
        ?string $code,
        ?Authenticatable $user = null,
        string $field = 'security_plan'
    ): array {
        $code = $this->normaliseCode((string) $code);

        $offered = collect($this->plans($type))->pluck('code')->all();

        if ($code === '') {
            throw new SecurityPlanException(
                $offered === []
                    ? sprintf(
                        'No %s lending plans are configured. A %s security cannot be pledged until one is set in System Settings.',
                        $type->label(),
                        $type->label()
                    )
                    : sprintf('Select a lending plan for this %s.', $type->label()),
                $field,
                422,
                $this->plansFor($type, $user)
            );
        }

        $plan = $this->plan($type, $code);

        if (!$plan) {
            throw new SecurityPlanException(
                sprintf(
                    '%s is not a %s lending plan. %s',
                    $code,
                    $type->label(),
                    $offered === []
                        ? 'None are configured.'
                        : 'Choose one of: ' . implode(', ', $offered) . '.'
                ),
                $field,
                422,
                $this->plansFor($type, $user)
            );
        }

        if (!$this->mayUse($plan['code'], $user)) {
            // Only the plans this person may actually use come back, so the
            // wizard re-renders with something they can pick rather than the
            // same refused plan in the list.
            $allowed = array_values(array_filter(
                $this->plansFor($type, $user),
                fn (array $offeredPlan): bool => $offeredPlan['allowed']
            ));

            throw new SecurityPlanException(
                sprintf(
                    'You do not have permission to use %s. Ask an administrator to grant the %s permission.',
                    $plan['label'],
                    self::permissionFor($plan['code'])
                ),
                $field,
                403,
                $allowed
            );
        }

        return $plan;
    }

    /**
     * The percentage of a stored or submitted security's plan, or null when the
     * plan cannot be resolved -- which is what makes the security unmeasured
     * rather than measured at some assumed rate.
     */
    public function percentageFor(LoanSecurityType $type, ?string $code): ?float
    {
        return $this->plan($type, $code)['percentage'] ?? null;
    }

    /**
     * Every security in a `securities` payload checked and normalised, each one
     * naming a plan it may use.
     *
     * The offending item is reported at its own position, securities.1.security_plan,
     * so the wizard can point at the card rather than the list. The type has
     * already been validated as a real type by the time this is reached, which
     * is why the type is read straight off the payload.
     *
     * The returned securities carry the plan as the officer wrote it, unchanged:
     * this checks, it does not correct. A code differing only in case is left as
     * sent and matched case-insensitively downstream, so the value stored is the
     * value the payload carried.
     *
     * @param array<int, array<string, mixed>> $securities
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws SecurityPlanException
     */
    public function assertAllUsable(array $securities, ?Authenticatable $user = null): array
    {
        foreach ($securities as $index => $security) {
            $this->assertUsable(
                LoanSecurityType::from($security['security_type']),
                $security['security_plan'] ?? null,
                $user,
                sprintf('securities.%s.security_plan', $index)
            );
        }

        return $securities;
    }

    /** Lower-cased and trimmed, so "Plan_1" and " plan_1 " are the same plan. */
    private static function normaliseCode(string $code): string
    {
        return strtolower(trim($code));
    }

    /** plan_1 -> "Plan 1", for a setting entry that gives no label of its own. */
    private function defaultLabel(string $code): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $code));
    }
}