<?php

namespace App\Traits;

use App\Enums\LoanSecurityType;
use App\Models\LoanApplicationSecurity;
use App\Models\LoanProduct;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * The `security` object on the loan application create and update requests.
 *
 * One security per application, of one of three types. A type's fields are
 * required only when that type is chosen and refused when another one is, so
 * a Property Mortgage payload cannot carry a stray policy number into the row.
 * Whether a security is wanted at all follows the loan type: Standard
 * Borrowing under the General term (LoanProduct::requiresSecurity()) -- see
 * checkSecurityAgainstProduct().
 */
trait ValidatesLoanSecurity
{
    /**
     * @return array<string, mixed>
     */
    protected function loanSecurityRules(): array
    {
        // required_if the type is chosen, refused unless it is.
        $for = fn (LoanSecurityType $type, bool $required = true) => array_values(array_filter([
            $required ? 'required_if:security.security_type,' . $type->value : null,
            'prohibited_unless:security.security_type,' . $type->value,
            'nullable',
        ]));

        $cdp      = LoanSecurityType::CdpInvestment;
        $property = LoanSecurityType::PropertyMortgage;
        $vehicle  = LoanSecurityType::Vehicle;
        $thisYear = (int) now()->year;

        return [
            'security'               => 'nullable|array',
            'security.security_type' => ['required_with:security', Rule::enum(LoanSecurityType::class)],

            // CDP Investment -- the policy itself is checked against CDP Core
            // by LoanSecurityService once the request is valid.
            'security.investment_nic' => [...$for($cdp), 'string', 'max:20'],
            'security.policy_number'  => [...$for($cdp), 'string', 'max:100'],

            // Property Mortgage
            'security.owner_name'        => [...$for($property), 'string', 'max:255'],
            'security.property_type'     => [...$for($property), Rule::in(array_keys(LoanApplicationSecurity::PROPERTY_TYPES))],
            'security.owner_deed_number' => [...$for($property), 'string', 'max:100'],

            // Property Evaluation -- optional, Property Mortgage only.
            'security.estimated_value'    => [...$for($property, false), 'numeric', 'min:0', 'max:9999999999999.99'],
            'security.evaluation_date'    => [...$for($property, false), 'date', 'before_or_equal:today'],
            'security.evaluated_by'       => [...$for($property, false), 'string', 'max:255'],
            'security.evaluation_remarks' => [...$for($property, false), 'string', 'max:2000'],

            // Vehicle -- the CR and the valuation report are uploaded as documents.
            'security.vehicle_make'          => [...$for($vehicle), 'string', 'max:100'],
            'security.vehicle_model'         => [...$for($vehicle), 'string', 'max:100'],
            'security.year_of_manufacture'   => [...$for($vehicle), 'integer', 'min:1900', "max:{$thisYear}"],
            'security.year_of_registration'  => [...$for($vehicle), 'integer', 'min:1900', "max:{$thisYear}", 'gte:security.year_of_manufacture'],
            'security.registration_number'   => [...$for($vehicle), 'string', 'max:50'],
            'security.chassis_engine_number' => [...$for($vehicle, false), 'string', 'max:100'],
        ];
    }

    /**
     * Human names for the security fields, so messages read "owner deed
     * number" rather than "security.owner_deed_number".
     *
     * @return array<string, string>
     */
    protected function loanSecurityAttributes(): array
    {
        return [
            'security'                      => 'loan security',
            'security.security_type'        => 'security type',
            'security.investment_nic'       => 'NIC',
            'security.policy_number'        => 'investment policy number',
            'security.owner_name'           => 'owner name',
            'security.property_type'        => 'property type',
            'security.owner_deed_number'    => 'owner deed number',
            'security.estimated_value'      => 'estimated value',
            'security.evaluation_date'      => 'evaluation date',
            'security.evaluated_by'         => 'evaluator',
            'security.evaluation_remarks'   => 'evaluation remarks',
            'security.vehicle_make'         => 'vehicle make',
            'security.vehicle_model'        => 'vehicle model',
            'security.year_of_manufacture'  => 'year of manufacture',
            'security.year_of_registration' => 'year of registration',
            'security.registration_number'  => 'registration number',
            'security.chassis_engine_number' => 'chassis / engine number',
        ];
    }

    /**
     * "Enter the owner deed number for a Property Mortgage security." /
     * "The vehicle make only applies to a Vehicle security." -- generated
     * per type from LoanSecurityType::fields().
     *
     * @return array<string, string>
     */
    protected function loanSecurityMessages(): array
    {
        $messages = [
            'security.year_of_registration.gte'        => 'The year of registration cannot be before the year of manufacture.',
            'security.evaluation_date.before_or_equal' => 'The evaluation date cannot be in the future.',
        ];

        $names = $this->loanSecurityAttributes();

        foreach (LoanSecurityType::cases() as $type) {
            foreach ($type->fields() as $field) {
                $name = $names["security.{$field}"];

                $messages["security.{$field}.required_if"]       = "Enter the {$name} for a {$type->label()} security.";
                $messages["security.{$field}.prohibited_unless"] = "The {$name} only applies to a {$type->label()} security.";
            }
        }

        return $messages;
    }

    /**
     * The product's loan type decides whether a security is wanted: missing on
     * a Standard Borrowing loan, or sent for any other, is an error on
     * `security`. $alreadySecured lets an update to a loan that has its
     * security leave it out of the payload.
     */
    protected function checkSecurityAgainstProduct(Validator $validator, ?LoanProduct $product, bool $alreadySecured = false): void
    {
        if (!$product || $validator->errors()->has('loan_product_id')) {
            return;
        }

        $sent = !empty($this->input('security'));
        $secured = $product->requiresSecurity();

        if ($secured && !$sent && !$alreadySecured) {
            $validator->errors()->add(
                'security',
                "{$product->name} is a Standard Borrowing loan, so it must be secured. Add the loan security: CDP Investment, Property Mortgage or Vehicle."
            );
        }

        if (!$secured && $sent) {
            $validator->errors()->add(
                'security',
                "{$product->name} does not take a loan security. Only Standard Borrowing loans under the General term are secured."
            );
        }
    }
}
