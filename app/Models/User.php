<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Columns to select when a user is loaded as a nested reference — the account
     * behind an employee, a customer, or an audit-log entry.
     *
     * $hidden already strips the password and the verification tokens, but this
     * keeps the login trail (last_login_at) and account plumbing out of the query
     * entirely. UserController's own index/show select normally.
     *
     * @see \App\Models\Customer::SUMMARY_COLUMNS
     */
    public const SUMMARY_COLUMNS = 'id,name,username,email,user_type,employee_id,customer_id,is_active';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'password_changed_at',
        // Absent from this list until now, while AuthController::verifyOtp()
        // set it through a mass-assigning update(). The write was silently
        // dropped every time, so no customer was ever recorded as having
        // cleared two-factor and login() re-sent a fresh OTP on every single
        // sign-in, forever.
        'two_factor_verified_at',
        'user_type',
        'employee_id',
        'customer_id',
        'is_active',
        'can_login',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'email_verification_token',
        'email_verification_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'email_verification_token_expires_at',
        'password_changed_at',
        'two_factor_verified_at',
        'last_login_ip',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'can_login' => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'user_type' => $this->user_type,
        ];
    }

    /* Relationships */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The role that is allowed to do anything, so every guard that means
     * "unless they are the Super Admin" asks here.
     *
     * Compared case-insensitively on purpose. Spatie's hasRole() matches the
     * role name exactly in PHP, and the role is seeded as 'SUPER ADMIN', so
     * hasRole('Super Admin') is false -- a mismatch that silently locks the
     * Super Admin out of the very checks written to let them through.
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles->contains(
            fn ($role) => strcasecmp((string) $role->name, 'Super Admin') === 0
        );
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /* Accessors */

    public function getBranchAttribute()
    {
        return $this->employee?->branch;
    }

    /*
     * Zone, region and province fall back to the branch.
     *
     * An employee row carries its own zonal_id, region_id and province_id, but
     * they are left empty in practice -- staff are posted to a branch and
     * nothing asks the poster to restate the three tiers above it. The branch
     * already knows all three, and a branch cannot sit in two zones, so
     * reading them from there is not a guess.
     */
    public function getZoneAttribute()
    {
        return $this->employee?->zonal ?? $this->employee?->branch?->zonal;
    }

    public function getRegionAttribute()
    {
        return $this->employee?->region ?? $this->employee?->branch?->region;
    }

    public function getProvinceAttribute()
    {
        return $this->employee?->province ?? $this->employee?->branch?->province;
    }

    public function getParentAttribute()
    {
        return $this->employee?->reportingManager?->user;
    }

    public function getChildrenAttribute()
    {
        if (!$this->employee_id) {
            return collect();
        }
        $subordinateIds = Employee::where('reporting_manager_id', $this->employee_id)->pluck('id');
        return User::whereIn('employee_id', $subordinateIds)->get();
    }

    /**
     * Whether this user still has to set a password of their own.
     *
     * The timestamp itself stays hidden -- when someone last changed their
     * password is nobody else's business -- but the frontend needs the yes/no
     * to send them to the change-password dialog instead of letting them walk
     * into a wall of 403s from EnsurePasswordChanged.
     */
    public function getPasswordChangeRequiredAttribute(): bool
    {
        // Only answer when the column was actually loaded. Plenty of endpoints
        // select a handful of user columns for a nested reference, and reading
        // a missing attribute as null made every one of those users look like
        // they had never set a password.
        if (!array_key_exists('password_changed_at', $this->attributes)) {
            return false;
        }

        return $this->password_changed_at === null;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['password_change_required'] = $this->password_change_required;

        // If employee relationship is loaded, we can populate branch, zone, region, province
        if ($this->relationLoaded('employee') && $this->employee) {
            $branch = $this->employee->relationLoaded('branch') ? $this->employee->branch : null;

            // Same fallback as the accessors above, but without ever going to
            // the database: only relations the caller already loaded are read,
            // so serialising a page of users cannot turn into four queries a
            // row. A caller that wants these filled loads them.
            $posting = function (string $employeeRelation) use ($branch) {
                if ($this->employee->relationLoaded($employeeRelation) && $this->employee->{$employeeRelation}) {
                    return $this->employee->{$employeeRelation};
                }

                return $branch && $branch->relationLoaded($employeeRelation)
                    ? $branch->{$employeeRelation}
                    : null;
            };

            $array['branch'] = $branch;
            $array['zone'] = $posting('zonal');
            $array['region'] = $posting('region');
            $array['province'] = $posting('province');

            if ($this->employee->relationLoaded('reportingManager') && $this->employee->reportingManager) {
                if ($this->employee->reportingManager->relationLoaded('user') && $this->employee->reportingManager->user) {
                    $parentUser = $this->employee->reportingManager->user;
                    $array['parent'] = [
                        'id' => $parentUser->id,
                        'name' => $parentUser->name,
                        'username' => $parentUser->username,
                        'email' => $parentUser->email,
                        'user_type' => $parentUser->user_type,
                        'is_active' => $parentUser->is_active,
                        'can_login' => $parentUser->can_login,
                    ];
                } else {
                    $array['parent'] = null;
                }
            } else {
                $array['parent'] = null;
            }

            if ($this->employee->relationLoaded('subordinates')) {
                $array['children'] = $this->employee->subordinates
                    ->map(function ($sub) {
                        return $sub->relationLoaded('user') && $sub->user ? [
                            'id' => $sub->user->id,
                            'name' => $sub->user->name,
                            'username' => $sub->user->username,
                            'email' => $sub->user->email,
                            'user_type' => $sub->user->user_type,
                            'is_active' => $sub->user->is_active,
                            'can_login' => $sub->user->can_login,
                        ] : null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            } else {
                $array['children'] = [];
            }
        } else {
            $array['branch'] = null;
            $array['zone'] = null;
            $array['region'] = null;
            $array['province'] = null;
            $array['parent'] = null;
            $array['children'] = [];
        }

        return $array;
    }

    /* Helper Methods */
    public function canLogin(): bool
    {
        $canLogin = $this->is_active && $this->can_login;

        if ($this->employee_id && $this->relationLoaded('employee')) {
            return $canLogin && $this->employee && $this->employee->is_active;
        }

        // If not loaded, check existence
        if ($this->employee_id) {
            return $canLogin && $this->load('employee')->employee->is_active;
        }

        return $canLogin;
    }

    public function updateLastLogin($ipAddress = null)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress
        ]);
    }

    /**
     * Generate a unique email verification token
     */
    public function generateEmailVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update([
            'email_verification_token' => $token,
            'email_verification_token_expires_at' => now()->addHours(24)
        ]);

        return $token;
    }

    /**
     * Mark the user's email as verified
     */
    public function markEmailAsVerifiedcheck(string $token)
    {
        $this->update([
            'email_verified_at' => now(),
            'email_verification_token' => $token,
            'email_verification_token_expires_at' => null
        ]);
    }

    /**
     * Mark the user's email as verified without a token
     */
    public function markEmailAsVerified()
    {
        $this->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_token_expires_at' => null
        ]);
    }

    /**
     * Check if the user's email verification token is valid
     */
    public function isEmailVerificationTokenValid(string $token): bool
    {
        if ($this->email_verification_token !== $token) {
            return false;
        }

        if (!$this->email_verification_token_expires_at) {
            return false;
        }

        return now()->lessThan($this->email_verification_token_expires_at);
    }

    /**
     * Get all direct and indirect subordinate IDs (descendants).
     */
    public function getAllDescendantIds(): array
    {
        if (!$this->employee_id) {
            return [];
        }

        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->getAllDescendantUserIds();
        }

        return $this->load('employee')->employee->getAllDescendantUserIds();
    }

    /**
     * Check if the user has verified their email
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

}
