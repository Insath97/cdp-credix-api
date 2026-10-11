<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * A customer's bank details, assets and liabilities are what their live loan
 * shows (see UsedForCustomerLoan). While such a loan exists, only a user
 * holding the override permission may add, change or remove them, so the data
 * a reviewer checked and the agreement printed cannot shift underneath the
 * loan. It is a permission, not a role, so it can be granted from the Roles
 * screen to whoever needs it.
 */
trait GuardsCustomerLoanRecords
{
    public const LIVE_LOAN_OVERRIDE_PERMISSION = 'Customer Financials Live Loan Override';

    /**
     * The 403 to send when any of these customers is on a live loan and the
     * user lacks the override permission; null when the change may go ahead.
     */
    protected function customerRecordsLocked(mixed ...$customerIds): ?JsonResponse
    {
        $user = Auth::user();
        if ($user instanceof User && $user->can(self::LIVE_LOAN_OVERRIDE_PERMISSION)) {
            return null;
        }

        foreach (array_unique(array_filter($customerIds, 'is_numeric')) as $customerId) {
            if (LoanApplication::live()->forCustomer((int) $customerId)->exists()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "This customer is on a live loan, so their bank details, assets and liabilities can't be added, changed or removed without the live-loan override permission.",
                ], 403);
            }
        }

        return null;
    }

    /**
     * The customer form's record lists: payload key => [relation, the fields
     * the form sends => [type, value when the form leaves it out]].
     */
    private const CUSTOMER_RECORD_LISTS = [
        'bank_details' => ['bankDetails', [
            'bank_name'      => ['string', null],
            'branch_name'    => ['string', null],
            'account_number' => ['string', null],
            'payment_method' => ['string', 'bank_transfer'],
            'is_active'      => ['bool', true],
        ]],
        'fixed_assets' => ['fixedAssets', [
            'owner_name'        => ['string', null],
            'property_location' => ['string', null],
            'extent'            => ['string', null],
            'market_value'      => ['money', null],
            'is_mortaged'       => ['bool', false],
            'is_active'         => ['bool', true],
        ]],
        'moving_assets' => ['movingAssets', [
            'assest_category'            => ['string', null],
            'owner_name'                 => ['string', null],
            'no_of_shares'               => ['int', null],
            'par_value'                  => ['money', null],
            'registation_no'             => ['string', null],
            'market_value'               => ['money', null],
            'mortgage_lease_hire_status' => ['string', null],
            'is_active'                  => ['bool', true],
        ]],
        'liabilities' => ['liabilities', [
            'liability_type'       => ['string', null],
            'institution_name'     => ['string', null],
            'account_reference_no' => ['string', null],
            'original_amount'      => ['money', null],
            'outstanding_balance'  => ['money', null],
            'monthly_installment'  => ['money', null],
            'start_date'           => ['date', null],
            'end_date'             => ['date', null],
            'is_active'            => ['bool', true],
        ]],
    ];

    /**
     * Which of the customer form's record lists the payload actually changes.
     *
     * The form sends every list in full on every save. A list it leaves out,
     * or sends back exactly as stored (in any order), is not a change. Only
     * the fields the form can send are compared, so values it cannot carry
     * (a moving asset's make_model, say) never count as a difference.
     *
     * @return array<int, string> payload keys, e.g. ['bank_details']
     */
    protected function changedCustomerRecordLists(Customer $customer, array $data): array
    {
        $changed = [];

        foreach (self::CUSTOMER_RECORD_LISTS as $key => [$relation, $fields]) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $stored = $customer->{$relation}()->get()
                ->map(fn ($row) => $this->recordFingerprint($fields, $row->getAttributes()))
                ->sort()->values()->all();

            $sent = collect($data[$key] ?? [])
                ->map(fn ($row) => $this->recordFingerprint($fields, (array) $row))
                ->sort()->values()->all();

            if ($stored !== $sent) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    private function recordFingerprint(array $fields, array $row): string
    {
        $out = [];

        foreach ($fields as $field => [$type, $default]) {
            $value = $row[$field] ?? null;
            $value = ($value === null || $value === '') ? $default : $value;

            $out[$field] = $value === null ? null : match ($type) {
                'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'int'   => (int) $value,
                'money' => number_format((float) $value, 2, '.', ''),
                'date'  => Carbon::parse($value)->format('Y-m-d'),
                default => trim((string) $value),
            };
        }

        return json_encode($out);
    }
}
