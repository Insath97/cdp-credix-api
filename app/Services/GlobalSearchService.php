<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Collection;


class GlobalSearchService
{
    /**
     * How a person is attached to a loan. The wording is what the caller
     * displays, so it is defined once here rather than in a controller.
     */
    public const ROLE_BORROWER     = 'customer';
    public const ROLE_CO_BORROWER  = 'joint_borrower';
    public const ROLE_GROUP_MEMBER = 'group_member';
    public const ROLE_GUARANTOR    = 'guarantor';

    /**
     * Everything known about the holder of one identification document.
     */
    public function search(string $idType, string $idNumber): array
    {
        $employees  = $this->matchingEmployees($idType, $idNumber);
        $customers  = $this->matchingCustomers($idType, $idNumber);
        $guarantors = $this->matchingGuarantors($idType, $idNumber);

        $loans = $this->loansForCustomers($customers)
            ->merge($this->loansForGuarantors($guarantors))
            ->sortByDesc(fn (array $loan) => $loan['applied_at'] ?? '')
            ->values();

        return [
            'identification' => [
                'id_type'   => $idType,
                'id_number' => $idNumber,
            ],
            'found'    => $employees->isNotEmpty() || $customers->isNotEmpty() || $guarantors->isNotEmpty(),
            'person'   => $this->identity($employees, $customers, $guarantors),
            'profiles' => [
                'employee'  => $employees->map(fn (Employee $e) => $this->employeeProfile($e))->values(),
                'customer'  => $customers->map(fn (Customer $c) => $this->customerProfile($c))->values(),
                'guarantor' => $guarantors->map(fn (Guarantor $g) => $this->guarantorProfile($g))->values(),
            ],
            'loans'   => $loans,
            'summary' => $this->summary($employees, $customers, $guarantors, $loans),
        ];
    }


    private function applyIdentification($query, string $idType, string $idNumber)
    {
        return $query
            ->whereRaw('LOWER(TRIM(id_type)) = ?', [mb_strtolower(trim($idType))])
            ->whereRaw('LOWER(TRIM(id_number)) = ?', [mb_strtolower(trim($idNumber))]);
    }

    private function matchingEmployees(string $idType, string $idNumber): Collection
    {
        return $this->applyIdentification(Employee::query(), $idType, $idNumber)
            ->with(['branch:id,name,code', 'designation:id,name', 'department:id,name', 'user:id,username,user_type,employee_id,is_active,can_login'])
            ->get();
    }

    private function matchingCustomers(string $idType, string $idNumber): Collection
    {
        return $this->applyIdentification(Customer::query(), $idType, $idNumber)
            ->with(['branch:id,name,code', 'user:id,username,user_type,customer_id,is_active,can_login'])
            ->get();
    }

    private function matchingGuarantors(string $idType, string $idNumber): Collection
    {
        return $this->applyIdentification(Guarantor::query(), $idType, $idNumber)
            ->with(['customer:'.Customer::SUMMARY_COLUMNS])
            ->get();
    }


    private function identity(Collection $employees, Collection $customers, Collection $guarantors): ?array
    {
        $employee  = $employees->first();
        $customer  = $customers->first();
        $guarantor = $guarantors->first();

        if (!$employee && !$customer && !$guarantor) {
            return null;
        }

        return [
            'full_name'     => $employee?->full_name ?? $customer?->full_name ?? $guarantor?->full_name,
            'date_of_birth' => $employee?->date_of_birth ?? $customer?->date_of_birth ?? $guarantor?->date_of_birth,
            'phone'         => $employee?->phone_primary ?? $customer?->phone_primary ?? $guarantor?->phone_primary,
            'email'         => $employee?->email ?? $customer?->email,
            'known_as'      => collect([
                $employees->isNotEmpty()  ? 'employee'  : null,
                $customers->isNotEmpty()  ? 'customer'  : null,
                $guarantors->isNotEmpty() ? 'guarantor' : null,
            ])->filter()->values()->all(),
        ];
    }

    private function employeeProfile(Employee $employee): array
    {
        return [
            'id'            => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name'     => $employee->full_name,
            'email'         => $employee->email,
            'phone'         => $employee->phone_primary ?? $employee->phone,
            'branch'        => $employee->branch?->name,
            'department'    => $employee->department?->name,
            'designation'   => $employee->designation?->name,
            'is_active'     => (bool) $employee->is_active,
            'user_account'  => $this->userAccount($employee->user),
        ];
    }

    private function customerProfile(Customer $customer): array
    {
        return [
            'id'            => $customer->id,
            'customer_code' => $customer->customer_code,
            'full_name'     => $customer->full_name,
            'email'         => $customer->email,
            'phone'         => $customer->phone_primary,
            'branch'        => $customer->branch?->name,
            'credit_score'  => $customer->credit_score,
            'is_active'     => (bool) $customer->is_active,
            'user_account'  => $this->userAccount($customer->user),
        ];
    }

    private function guarantorProfile(Guarantor $guarantor): array
    {
        return [
            'id'                => $guarantor->id,
            'full_name'         => $guarantor->full_name,
            'phone'             => $guarantor->phone_primary,
            'employment_status' => $guarantor->employment_status,
            'occupation'        => $guarantor->occupation,
            'employer_name'     => $guarantor->employer_name,
            'guarantees_for'    => $guarantor->customer ? [
                'customer_id'   => $guarantor->customer->id,
                'customer_code' => $guarantor->customer->customer_code,
                'full_name'     => $guarantor->customer->full_name,
            ] : null,
        ];
    }

    private function userAccount(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id'        => $user->id,
            'username'  => $user->username,
            'user_type' => $user->user_type,
            'is_active' => (bool) $user->is_active,
            'can_login' => (bool) $user->can_login,
        ];
    }


    private function loansForCustomers(Collection $customers): Collection
    {
        if ($customers->isEmpty()) {
            return collect();
        }

        $customerIds = $customers->pluck('id')->all();

        $applications = LoanApplication::query()
            ->where(function ($query) use ($customerIds) {
                $query->whereIn('customer_id', $customerIds)
                    ->orWhereHas('loanApplicationCustomers', fn ($q) => $q->whereIn('customer_id', $customerIds));
            })
            ->with($this->loanRelations())
            ->get();

        return $applications->map(function (LoanApplication $application) use ($customerIds) {
            $isPrimary = in_array((int) $application->customer_id, $customerIds, true);

            $role = $isPrimary
                ? self::ROLE_BORROWER
                : ($application->group_loan_id !== null ? self::ROLE_GROUP_MEMBER : self::ROLE_CO_BORROWER);

            return $this->loanEntry($application, $role);
        });
    }


    private function loansForGuarantors(Collection $guarantors): Collection
    {
        if ($guarantors->isEmpty()) {
            return collect();
        }

        $guarantorIds = $guarantors->pluck('id')->all();

        $applications = LoanApplication::query()
            ->whereHas('loanApplicationGuarantors', fn ($q) => $q->whereIn('guarantor_id', $guarantorIds))
            ->with(array_merge($this->loanRelations(), [
                'loanApplicationGuarantors' => fn ($q) => $q->whereIn('guarantor_id', $guarantorIds),
            ]))
            ->get();

        return $applications->map(function (LoanApplication $application) {
            $pivot = $application->loanApplicationGuarantors->first();

            return $this->loanEntry($application, self::ROLE_GUARANTOR, [
                'guarantor_type'   => $pivot?->guarantor_type,
                'guarantor_status' => $pivot?->status,
            ]);
        });
    }

    /**
     * The relations to eager load on a loan application.
     */
    private function loanRelations(): array
    {
        return [
            'application:id,application_no',
            'customer:'.Customer::SUMMARY_COLUMNS,
            'loanProduct:id,name',
            'branch:id,name,code',
        ];
    }

    /**
     * One loan as it appears in the consolidated view.
     */
    private function loanEntry(LoanApplication $application, string $role, array $extra = []): array
    {
        return array_merge([
            'loan_application_id'   => $application->id,
            'application_no'        => $application->application?->application_no,
            'approval_reference_no' => $application->approval_reference_no,
            // What this person was to this loan. The whole point of the search.
            'role'                  => $role,
            'is_group_loan'         => $application->group_loan_id !== null,
            'status'                => $application->status?->value,
            'status_label'          => $application->status?->name,
            'is_active'             => (bool) $application->is_active,
            'loan_product'          => $application->loanProduct?->name,
            'branch'                => $application->branch?->name,
            // Named so a guarantor's row says whose loan they are standing on.
            'borrower'              => $application->customer ? [
                'customer_id'   => $application->customer->id,
                'customer_code' => $application->customer->customer_code,
                'full_name'     => $application->customer->full_name,
            ] : null,
            'requested_amount'      => $application->requested_amount,
            'approved_amount'       => $application->approved_amount,
            'outstanding_balance'   => $application->outstanding_balance,
            'term_months'           => $application->term_months,
            'interest_rate'         => $application->interest_rate,
            'applied_at'            => optional($application->applied_at)->toDateTimeString(),
            'approved_at'           => optional($application->approved_at)->toDateTimeString(),
            'disbursed_at'          => optional($application->disbursed_at)->toDateTimeString(),
        ], $extra);
    }

    private function summary(Collection $employees, Collection $customers, Collection $guarantors, Collection $loans): array
    {
        $byRole = $loans->groupBy('role')->map->count();

        return [
            'is_employee'        => $employees->isNotEmpty(),
            'is_customer'        => $customers->isNotEmpty(),
            'is_guarantor'       => $guarantors->isNotEmpty(),
            'total_loans'        => $loans->count(),
            'loans_as_borrower'  => $byRole[self::ROLE_BORROWER] ?? 0,
            'loans_as_co_borrower' => $byRole[self::ROLE_CO_BORROWER] ?? 0,
            'loans_as_group_member' => $byRole[self::ROLE_GROUP_MEMBER] ?? 0,
            'loans_as_guarantor' => $byRole[self::ROLE_GUARANTOR] ?? 0,
        ];
    }
}
