<?php

namespace App\Services;

use App\Exceptions\CustomerHasLiveLoanException;
use App\Models\Customer;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One live loan per customer.
 *
 * A customer who is already on a live loan -- individual, joint or group, as
 * the primary borrower, a co-borrower or a group member -- may not be put on
 * another one until it is closed, rejected, cancelled or declined. "Live" is
 * LoanApplicationStatus::holdsBorrowers(), and it includes the whole review
 * pipeline, not only disbursed loans.
 *
 * Standing guarantor for someone else's loan does NOT count: that has its own
 * ceiling in GuarantorLoanLimitService.
 *
 * A person is matched by customer id AND by NIC. The same human being can sit
 * under several customer rows -- a typed-in group member creates a fresh
 * customer every time -- so a check on the id alone would let them straight
 * through on the second row. Old (9 digits + V/X) and new (12 digit) Sri Lankan
 * NIC formats are treated as the same number.
 *
 * Every create and update path that puts a customer on a loan asks here, so
 * the rule and its wording live in one place.
 */
class CustomerLoanEligibilityService
{
    /** SQL that normalises an NIC column the same way normalizeNic() does. */
    private const NIC_SQL = "UPPER(REPLACE(REPLACE(TRIM(%s), ' ', ''), '-', ''))";

    // ------------------------------------------------------------------
    // Checks used by FormRequests: return a message, or null when allowed.
    // ------------------------------------------------------------------

    /**
     * Why this existing customer may not go on a new loan, or null if they may.
     *
     * @param int|null $excludeLoanApplicationId The loan they are being put on,
     *        when it already exists (adding a joint member, reopening). It is
     *        left out so the loan does not count against itself.
     */
    public static function refusalForCustomer(int $customerId, ?int $excludeLoanApplicationId = null): ?string
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            return null; // 'exists' rule reports this one
        }

        $loan = self::liveLoanForPerson([$customer->id], $customer->id_number, $excludeLoanApplicationId);

        return $loan ? self::message($customer->full_name, $customer->id_number, $loan, [$customer->id]) : null;
    }

    /**
     * The same, for a person typed in by NIC with no customer picked.
     */
    public static function refusalForNic(?string $nic, ?string $name = null, ?int $excludeLoanApplicationId = null): ?string
    {
        if (self::normalizeNic($nic) === '') {
            return null;
        }

        $loan = self::liveLoanForPerson([], $nic, $excludeLoanApplicationId);

        return $loan ? self::message($name, $nic, $loan, []) : null;
    }

    // ------------------------------------------------------------------
    // Race-proof check used inside a create transaction.
    // ------------------------------------------------------------------

    /**
     * Lock the borrowers' customer rows and refuse if any holds a live loan.
     *
     * MUST be called inside DB::transaction(). The lock holds until commit, so
     * a second request for the same person waits here and then sees the loan
     * the first one created.
     *
     * @param array<string, int> $customerIdsByField  e.g. ['customer_id' => 5, 'joint_customer_ids.0' => 9].
     *        The key is the field the error is reported against.
     *
     * @throws CustomerHasLiveLoanException
     */
    public static function assertEligible(array $customerIdsByField, ?int $excludeLoanApplicationId = null): void
    {
        $customerIdsByField = array_filter(array_map('intval', $customerIdsByField));

        if ($customerIdsByField === []) {
            return;
        }

        // Lock every row that is the same person, not only the ids given: a
        // concurrent request may be using the person's other customer row.
        // Ordered by id so two requests never lock in opposite order (deadlock).
        // The NIC lookup is a plain read; only the primary keys it finds are
        // locked. Locking on the NIC expression itself would make InnoDB lock
        // every row it scans -- the whole customers table.
        $customers = Customer::whereIn('id', array_unique(array_values($customerIdsByField)))->get();
        $variants = $customers->flatMap(fn ($c) => self::nicVariants($c->id_number))->unique()->values()->all();

        $lockIds = array_values($customerIdsByField);
        if ($variants !== []) {
            $lockIds = array_merge(
                $lockIds,
                Customer::whereIn(DB::raw(sprintf(self::NIC_SQL, 'id_number')), $variants)->pluck('id')->all()
            );
        }

        Customer::whereIn('id', array_values(array_unique($lockIds)))
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        foreach ($customerIdsByField as $field => $customerId) {
            $customer = $customers->firstWhere('id', $customerId);
            if (!$customer) {
                continue;
            }

            $loan = self::liveLoanForPerson([$customer->id], $customer->id_number, $excludeLoanApplicationId);

            if ($loan) {
                throw new CustomerHasLiveLoanException(
                    self::message($customer->full_name, $customer->id_number, $loan, [$customer->id]),
                    (string) $field
                );
            }
        }
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * The first live loan this person is on, found by any of their customer
     * rows or by NIC on a group member snapshot.
     */
    private static function liveLoanForPerson(array $customerIds, ?string $nic, ?int $excludeLoanApplicationId): ?LoanApplication
    {
        $variants = self::nicVariants($nic);

        if ($variants !== []) {
            $customerIds = array_merge(
                $customerIds,
                Customer::whereIn(DB::raw(sprintf(self::NIC_SQL, 'id_number')), $variants)->pluck('id')->all()
            );
        }

        $customerIds = array_values(array_unique(array_map('intval', $customerIds)));

        if ($customerIds === [] && $variants === []) {
            return null;
        }

        return LoanApplication::query()
            ->live()
            ->where(function ($q) use ($customerIds, $variants) {
                if ($customerIds !== []) {
                    $q->whereIn('customer_id', $customerIds);
                }

                $q->orWhereHas('loanApplicationCustomers', function ($member) use ($customerIds, $variants) {
                    $member->where(function ($m) use ($customerIds, $variants) {
                        if ($customerIds !== []) {
                            $m->whereIn('customer_id', $customerIds);
                        }
                        // A group member's snapshot NIC can be edited away from
                        // the customer record, so it is matched on its own too.
                        if ($variants !== []) {
                            $m->orWhereIn(DB::raw(sprintf(self::NIC_SQL, 'nic')), $variants);
                        }
                    });
                });
            })
            ->when($excludeLoanApplicationId, fn ($q) => $q->where('id', '!=', $excludeLoanApplicationId))
            ->with(['application', 'groupLoan'])
            ->orderBy('id')
            ->first();
    }

    /**
     * Name the person and the loan, so the officer can see which file has to
     * be finished first -- and that it IS the same person when they were typed
     * in under another customer record.
     */
    private static function message(?string $name, ?string $nic, LoanApplication $loan, array $customerIds): string
    {
        $who = trim((string) $name) !== '' ? trim($name) : 'This customer';
        $idPart = self::normalizeNic($nic) !== '' ? ' (NIC: ' . trim($nic) . ')' : '';
        $status = Str::title(str_replace('_', ' ', $loan->status->value));
        $loanRef = "{$loan->reference()}, status: {$status}";

        // e.g. "Kamal Perera (NIC: 853400937V) already has an active loan
        // (APP-BRCOL-2026092300000012, status: Approved)."
        $first = match (self::roleOnLoan($loan, $customerIds)) {
            'member'      => "{$who}{$idPart} is already a member of an active group loan ({$loanRef}).",
            'co-borrower' => "{$who}{$idPart} is already a co-borrower on an active loan ({$loanRef}).",
            default       => "{$who}{$idPart} already has an active loan ({$loanRef}).",
        };

        return $first . ' A customer can have only one active loan at a time, so they cannot take another loan until the existing one is closed, rejected or cancelled.';
    }

    private static function roleOnLoan(LoanApplication $loan, array $customerIds): string
    {
        if ($loan->group_loan_id) {
            return 'member';
        }

        if ($customerIds === [] || in_array((int) $loan->customer_id, array_map('intval', $customerIds), true)) {
            return 'borrower';
        }

        return 'co-borrower';
    }

    /**
     * Upper-case, no spaces or dashes: "  853400937v " -> "853400937V".
     */
    public static function normalizeNic(?string $nic): string
    {
        return strtoupper(str_replace([' ', '-'], '', trim((string) $nic)));
    }

    /**
     * Every spelling of the same NIC, old and new format.
     *
     * Old: YY DDD SSS C + V/X     853400937V
     * New: YYYY DDD 0SSS C        198534000937
     *
     * @return array<int, string>
     */
    public static function nicVariants(?string $nic): array
    {
        $n = self::normalizeNic($nic);

        if ($n === '') {
            return [];
        }

        $variants = [$n];

        if (preg_match('/^(\d{2})(\d{3})(\d{3})(\d)[VX]$/', $n, $m)) {
            $variants[] = "19{$m[1]}{$m[2]}0{$m[3]}{$m[4]}";
            $variants[] = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}V";
            $variants[] = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}X";
        } elseif (preg_match('/^19(\d{2})(\d{3})0(\d{3})(\d)$/', $n, $m)) {
            $variants[] = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}V";
            $variants[] = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}X";
        }

        return array_values(array_unique($variants));
    }
}
