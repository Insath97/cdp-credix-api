<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    /**
     * Columns to select when an employee is loaded as a nested reference — the
     * staff member who applied for, reviewed, approved or received something.
     *
     * Those screens only need the name and posting, so the NIC, date of birth,
     * personal phone numbers, home address and employment dates are never
     * queried. EmployeeController's own index/show select normally.
     *
     * @see Customer::SUMMARY_COLUMNS
     */
    public const SUMMARY_COLUMNS = 'id,employee_code,f_name,l_name,full_name,name_with_initials,employee_type,branch_id,department_id,designation_id,province_id,region_id,zonal_id,reporting_manager_id,is_active';

    protected $fillable = [
        'f_name',
        'l_name',
        'full_name',
        'name_with_initials',
        'employee_code',
        'reporting_manager_id',
        'province_id',
        'region_id',
        'zonal_id',
        'branch_id',
        'department_id',
        'designation_id',
        'employee_type',
        'id_type',
        'id_number',
        'date_of_birth',
        'email',
        'phone',
        'address_line_1',
        'city',
        'state',
        'country',
        'postal_code',
        'phone_primary',
        'phone_secondary',
        'have_whatsapp',
        'whatsapp_number',
        'start_date',
        'end_date',
        'joined_at',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'have_whatsapp' => 'boolean',
        'date_of_birth' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'joined_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search employees by name, email, employee code, NIC or
     * phone.
     *
     * NIC and phone are here for the loan application's recommender picker: an
     * officer taking a loan usually has the recommending employee's card or
     * number in front of them, not the exact spelling of their name.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('f_name', 'like', "%{$search}%")
              ->orWhere('l_name', 'like', "%{$search}%")
              ->orWhere('full_name', 'like', "%{$search}%")
              ->orWhere('employee_code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('id_number', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('phone_primary', 'like', "%{$search}%");
        });
    }

    /**
     * Snapshot an employee's identifying details for the recommender columns
     * carried by customers and loan applications.
     *
     * The recommender fields store both an employee id and a snapshot of the
     * name, employee code, NIC and phone. Keeping the snapshot rows means the
     * customer or loan stays readable after the employee is deactivated or
     * their details change — the record always shows who recommended it.
     *
     * The per-record callers (customer / loan application controllers) send
     * the four snapshot values from the picker already; this helper is the
     * fallback so an API consumer that only sends recommended_by_employee_id
     * still stores the complete set.
     */
    public static function recommenderSnapshot(?int $id): array
    {
        if (!$id) {
            return [];
        }

        $employee = self::find($id);

        if (!$employee) {
            return [];
        }

        return [
            'recommended_by_employee_id' => $employee->id,
            'recommender_name'           => $employee->full_name,
            'recommender_employee_code'  => $employee->employee_code,
            'recommender_nic'            => $employee->id_number,
            'recommender_phone'          => $employee->phone ?: $employee->phone_primary,
        ];
    }

    /**
     * Fill any missing recommender snapshot fields on a validated payload.
     *
     * Values the client actually sent always win; the snapshot only stands in
     * for blank ones. Passing recommended_by_employee_id as null/empty clears
     * the carried snapshot columns too, so a recommender that is deliberately
     * removed from a customer or loan stops being displayed.
     */
    public static function mergeRecommenderSnapshot(array $data): array
    {
        $id = $data['recommended_by_employee_id'] ?? null;

        if ($id === null || $id === '') {
            if (array_key_exists('recommended_by_employee_id', $data)) {
                $data['recommender_name']          = null;
                $data['recommender_employee_code'] = null;
                $data['recommender_nic']           = null;
                $data['recommender_phone']         = null;
            }

            return $data;
        }

        foreach (self::recommenderSnapshot((int) $id) as $key => $value) {
            if (($data[$key] ?? null) === null || ($data[$key] ?? '') === '') {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Get the user account associated with the employee.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the reporting manager of the employee.
     */
    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    /**
     * Get the subordinates reporting to this employee.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reporting_manager_id');
    }

    /**
     * Get the province associated with the employee.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the region associated with the employee.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the zonal associated with the employee.
     */
    public function zonal(): BelongsTo
    {
        return $this->belongsTo(Zonal::class, 'zonal_id');
    }

    /**
     * Get the branch where the employee works.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department where the employee works.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the designation of the employee.
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Get all user IDs associated with subordinate employees (recursive).
     */
    public function getAllDescendantUserIds(): array
    {
        $userIds = [];
        $subordinates = Employee::where('reporting_manager_id', $this->id)->get();

        foreach ($subordinates as $subordinate) {
            $subUser = User::where('employee_id', $subordinate->id)->first();
            if ($subUser) {
                $userIds[] = $subUser->id;
                $userIds = array_merge($userIds, $subUser->getAllDescendantIds());
            } else {
                $userIds = array_merge($userIds, $subordinate->getAllDescendantUserIds());
            }
        }

        return $userIds;
    }
}
