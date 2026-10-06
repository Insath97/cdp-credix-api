<?php

namespace App\Traits;

use App\Enums\LoanSecurityType;
use App\Enums\PropertyType;
use App\Models\LendingMortgage;
use App\Models\LoanApplicationSecurity;
use App\Models\LoanProduct;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * The `securities` array on the loan application create and update requests.
 *
 * One or more securities per application, each of one of three types, each
 * independent -- a loan larger than any single asset is worth is secured by
 * two properties, or an investment and a vehicle, each under its own lending
 * plan. A type's fields are required only on that type's items and refused on
 * the others, so a Property Mortgage payload cannot carry a stray policy number
 * into the row.
 *
 * Every security names a plan and carries a value. The plan says what percentage
 * of the value a loan may reach (LoanSecurityPlanService); the value is what
 * that percentage is applied to (LoanSecurityLtvService): a property by its
 * valuation, a vehicle by its value, a CDP Investment by what CDP Core reports.
 * A CDP Investment has no value field of its own for the same reason -- the
 * backend will not take the collateral's worth from the frontend.
 *
 * Whether securities are wanted at all follows the loan type: Standard
 * Borrowing under the General term (LoanProduct::requiresSecurity()) -- see
 * checkSecuritiesAgainstProduct().
 */
trait ValidatesLoanSecurity
{
    /**
     * A ceiling on how many securities one loan may carry, so a mistyped
     * quantity cannot turn one mortgage into hundreds of rows. Set high enough
     * that no real file meets it.
     */
    public const MAX_SECURITIES_PER_APPLICATION = LoanApplicationSecurity::MAX_SECURITIES_PER_APPLICATION;

    /**
     * @return array<string, mixed>
     */
    protected function loanSecurityRules(): array
    {
        // required_if on that item's own security_type. The * in the parameter
        // is resolved per item, so each entry is required only on the entries
        // that chose that type.
        $for = fn (LoanSecurityType $type, bool $required = true) => array_values(array_filter([
            $required ? 'required_if:securities.*.security_type,' . $type->value : null,
            'prohibited_unless:securities.*.security_type,' . $type->value,
            'nullable',
        ]));

        $cdp      = LoanSecurityType::CdpInvestment;
        $property = LoanSecurityType::PropertyMortgage;
        $vehicle  = LoanSecurityType::Vehicle;
        $thisYear = (int) now()->year;

        return [
            'securities'                     => ['nullable', 'array', 'max:' . self::MAX_SECURITIES_PER_APPLICATION],
            'securities.*'                   => ['array'],
            'securities.*.security_type'     => ['required', Rule::enum(LoanSecurityType::class)],

            // The lending plan. Required on every security, with no fallback to
            // a type-wide percentage: the percentage a security lends at is
            // chosen here, so a security without one cannot be measured.
            //
            // Whether the plan exists for the type it was sent against, and
            // whether this person may use it, are decided by
            // LoanSecurityPlanService once the request is otherwise valid --
            // both need the type, and the permission needs the signed-in user,
            // neither of which belongs in a field rule.
            'securities.*.security_plan'     => ['required', 'string', 'max:50'],

            // Lending Mortgage reference
            'securities.*.lending_mortgage_id' => ['required', 'integer', 'exists:lending_mortgages,id'],

            // CDP Investment -- the policy itself is checked against CDP Core
            // by LoanSecurityService once the request is valid. Its value is
            // Core's to report, so there is no value field to accept here.
            'securities.*.investment_nic' => [...$for($cdp), 'string', 'max:20'],
            'securities.*.policy_number'  => [...$for($cdp), 'string', 'max:100'],

            // Property Mortgage
            'securities.*.owner_name'        => [...$for($property), 'string', 'max:255'],
            'securities.*.property_type'     => [...$for($property), Rule::enum(PropertyType::class)],
            'securities.*.owner_deed_number' => [...$for($property), 'string', 'max:100'],

            // Property Evaluation. Unlike before, the valuation is required: it
            // is what the percentage is applied to, so a property with no
            // valuation secures nothing and the loan cannot be assessed.
            'securities.*.estimated_value'    => [...$for($property), 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'securities.*.evaluation_date'    => [...$for($property, false), 'date', 'before_or_equal:today'],
            'securities.*.evaluated_by'       => [...$for($property, false), 'string', 'max:255'],
            'securities.*.evaluation_remarks' => [...$for($property, false), 'string', 'max:2000'],

            // Vehicle -- the CR and the valuation report are uploaded as documents.
            'securities.*.vehicle_make'          => [...$for($vehicle), 'string', 'max:100'],
            'securities.*.vehicle_model'         => [...$for($vehicle), 'string', 'max:100'],
            'securities.*.year_of_manufacture'   => [...$for($vehicle), 'integer', 'min:1900', "max:{$thisYear}"],
            'securities.*.year_of_registration'  => [...$for($vehicle), 'integer', 'min:1900', "max:{$thisYear}", 'gte:securities.*.year_of_manufacture'],
            'securities.*.registration_number'   => [...$for($vehicle), 'string', 'max:50'],
            'securities.*.chassis_engine_number' => [...$for($vehicle, false), 'string', 'max:100'],
            'securities.*.vehicle_value'         => [...$for($vehicle), 'numeric', 'min:0.01', 'max:9999999999999.99'],
        ];
    }

    /**
     * Human names for the security fields, so messages read "owner deed
     * number" rather than "securities.0.owner_deed_number".
     *
     * @return array<string, string>
     */
    protected function loanSecurityAttributes(): array
    {
        return [
            'securities'                       => 'loan securities',
            'securities.*'                     => 'loan security',
            'securities.*.security_type'       => 'security type',
            'securities.*.security_plan'       => 'lending plan',
            'securities.*.lending_mortgage_id'  => 'lending mortgage',
            'securities.*.investment_nic'      => 'NIC',
            'securities.*.policy_number'       => 'investment policy number',
            'securities.*.owner_name'          => 'owner name',
            'securities.*.property_type'       => 'property type',
            'securities.*.owner_deed_number'   => 'owner deed number',
            'securities.*.estimated_value'     => 'property valuation',
            'securities.*.evaluation_date'     => 'evaluation date',
            'securities.*.evaluated_by'        => 'evaluator',
            'securities.*.evaluation_remarks'  => 'evaluation remarks',
            'securities.*.vehicle_make'        => 'vehicle make',
            'securities.*.vehicle_model'       => 'vehicle model',
            'securities.*.year_of_manufacture' => 'year of manufacture',
            'securities.*.year_of_registration' => 'year of registration',
            'securities.*.registration_number' => 'registration number',
            'securities.*.chassis_engine_number' => 'chassis / engine number',
            'securities.*.vehicle_value'       => 'vehicle value',
        ];
    }

    /**
     * "Enter the owner deed number for a Property Mortgage security." /
     * "The vehicle make only applies to a Vehicle security." -- generated per
     * type from LoanSecurityType::fields(), and per item so the message points
     * at the right card ("Enter the property valuation for a Property security").
     *
     * @return array<string, string>
     */
    protected function loanSecurityMessages(): array
    {
        $messages = [
            'securities.max'                                 => 'A loan application can carry at most ' . self::MAX_SECURITIES_PER_APPLICATION . ' securities.',
            'securities.*.security_type.required'            => 'Choose the security or mortgage type for each security.',
            'securities.*.security_type.in'                  => 'That is not a security type this system supports.',
            'securities.*.security_plan.required'            => 'Choose the lending plan for each security. The percentage a security lends at comes from its plan, so a security without one secures nothing.',
            'securities.*.year_of_registration.gte'          => 'The year of registration cannot be before the year of manufacture.',
            'securities.*.evaluation_date.before_or_equal'   => 'The evaluation date cannot be in the future.',
        ];

        $names = $this->loanSecurityAttributes();

        foreach (LoanSecurityType::cases() as $type) {
            foreach ($type->fields() as $field) {
                $name = $names["securities.*.{$field}"];

                $messages["securities.*.{$field}.required_if"]       = "Enter the {$name} for a {$type->label()} security.";
                $messages["securities.*.{$field}.prohibited_unless"] = "The {$name} only applies to a {$type->label()} security.";
            }

            if ($field = $type->valueField()) {
                $name = $names["securities.*.{$field}"];

                $messages["securities.*.{$field}.required_if"] = "Enter the {$name} for a {$type->label()} security. "
                    . 'It is what the security limit is worked out from, so a security without one secures nothing.';
                $messages["securities.*.{$field}.min"]         = "The {$name} must be greater than 0.";
            }
        }

        return $messages;
    }

    /**
     * The product's loan type decides whether securities are wanted: none on a
     * Standard Borrowing loan, or any on another, is an error on `securities`.
     * $alreadySecured lets an update to a loan that has its securities leave
     * them out of the payload.
     */
    protected function checkSecuritiesAgainstProduct(Validator $validator, ?LoanProduct $product, bool $alreadySecured = false): void
    {
        if (!$product || $validator->errors()->has('loan_product_id')) {
            return;
        }

        $sent = $this->filled('securities') && is_array($this->input('securities'));
        $secured = $product->requiresSecurity();

        if ($secured && !$sent && !$alreadySecured) {
            $validator->errors()->add(
                'securities',
                "{$product->name} is a Standard Borrowing loan, so it must be secured. Add at least one loan security: CDP Investment, Property Mortgage or Vehicle."
            );
        }

        if (!$secured && $sent) {
            $validator->errors()->add(
                'securities',
                "{$product->name} does not take a loan security. Only Standard Borrowing loans under the General term are secured."
            );
        }
    }

    /**
     * An empty `securities: []` is a refusal dressed as a payload -- it reads
     * as "the officer sent securities" while carrying none, and would silently
     * strip a secured loan of the securities it already has.
     */
    protected function assertNoEmptySecurities(Validator $validator): void
    {
        if (!$this->has('securities')) {
            return;
        }

        if (!is_array($this->input('securities')) || $this->input('securities') !== []) {
            return;
        }

        $validator->errors()->add(
            'securities',
            'Send the securities to add, or leave `securities` out entirely. An empty list cannot mean "remove them all".'
        );
    }

    /**
     * The lending mortgage plan must belong to the same type as the security.
     * A Vehicle security cannot reference a Property mortgage plan, and so on.
     */
    protected function checkMortgageTypeMismatch(Validator $validator): void
    {
        $securities = $this->input('securities', []);

        if (!is_array($securities)) {
            return;
        }

        foreach ($securities as $i => $security) {
            $field = "securities.{$i}.lending_mortgage_id";

            // Skip if the field already has errors or is empty.
            if ($validator->errors()->has($field) || empty($security['lending_mortgage_id'])) {
                continue;
            }

            $mortgage = LendingMortgage::find($security['lending_mortgage_id']);

            if (!$mortgage) {
                continue; // The exists rule will catch this.
            }

            $securityType = LoanSecurityType::tryFrom($security['security_type'] ?? '');

            if (!$securityType) {
                continue; // The enum rule will catch this.
            }

            if ($mortgage->type !== $securityType->lendingMortgageType()) {
                $validator->errors()->add(
                    $field,
                    "The selected mortgage plan does not belong to the selected security type."
                );
            }
        }
    }

    /**
     * Normalize frontend field aliases for securities before validation runs.
     * E.g. property_owner -> owner_name, market_value -> estimated_value, etc.
     */
    protected function normalizeSecuritiesInput(): void
    {
        $securities = $this->input('securities');
        if (!is_array($securities)) {
            return;
        }

        foreach ($securities as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            // Normalize Property Mortgage fields
            $secType = $item['security_type'] ?? '';
            if ($secType === LoanSecurityType::PropertyMortgage->value || $secType === 'property') {
                if (!isset($item['owner_name']) && isset($item['property_owner'])) {
                    $item['owner_name'] = $item['property_owner'];
                }
                if (!isset($item['owner_deed_number']) && isset($item['deed_number'])) {
                    $item['owner_deed_number'] = $item['deed_number'];
                }
                if (!isset($item['owner_deed_number']) && isset($item['title_number'])) {
                    $item['owner_deed_number'] = $item['title_number'];
                }
                if (!isset($item['owner_deed_number']) && isset($item['deed_title_number'])) {
                    $item['owner_deed_number'] = $item['deed_title_number'];
                }
                if (!isset($item['estimated_value']) && isset($item['market_value'])) {
                    $item['estimated_value'] = $item['market_value'];
                }
                if (!isset($item['estimated_value']) && isset($item['estimated_market_value'])) {
                    $item['estimated_value'] = $item['estimated_market_value'];
                }
                if (!isset($item['evaluation_date']) && isset($item['valuation_date'])) {
                    $item['evaluation_date'] = $item['valuation_date'];
                }
                if (!isset($item['evaluated_by']) && isset($item['assessed_by'])) {
                    $item['evaluated_by'] = $item['assessed_by'];
                }
                if (!isset($item['evaluated_by']) && isset($item['evaluated_assessed_by'])) {
                    $item['evaluated_by'] = $item['evaluated_assessed_by'];
                }
                if (!isset($item['evaluation_remarks']) && isset($item['valuation_notes'])) {
                    $item['evaluation_remarks'] = $item['valuation_notes'];
                }
                if (!isset($item['evaluation_remarks']) && isset($item['land_description'])) {
                    $item['evaluation_remarks'] = $item['land_description'];
                }
            }

            $securities[$index] = $item;
        }

        $this->merge(['securities' => $securities]);
    }
}
