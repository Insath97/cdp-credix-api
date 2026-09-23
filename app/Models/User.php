<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
    /**
     * The username of the account that automated work is attributed to.
     *
     * The nightly schedulers move loan applications with nobody signed in.
     * Their audit rows still have to name someone, so they name this account:
     * it cannot log in and belongs to no person, which is exactly the point --
     * "the system did this" rather than a real officer who did not.
     */
    public const SYSTEM_USERNAME = 'system';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'password_changed_at',
        'password_expires_at',
        'password_locked_at',
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
        'password_expires_at',
        'password_locked_at',
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
            'password_expires_at' => 'datetime',
            'password_locked_at' => 'datetime',
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
    /**
     * The id of the System account, creating it if it is not there yet.
     *
     * Audit rows may not be left unattributed now that changed_by is required,
     * and the nightly schedulers have no signed-in user to name. This resolves
     * that: a real row in users, so the foreign key holds, but one that cannot
     * log in and has no password anyone could use.
     *
     * firstOrCreate rather than a plain lookup because this is reached from an
     * audit write at 2am. A database that has not been seeded, or was seeded
     * before this account existed, must not take the nightly job down -- it
     * heals itself instead.
     *
     * Memoised per process: the schedulers call this once per loan application
     * they move, and the answer cannot change while the process is running.
     */
    public static function systemUserId(): int
    {
        static $id = null;

        if ($id !== null) {
            return $id;
        }

        return $id = static::firstOrCreate(
            ['username' => self::SYSTEM_USERNAME],
            [
                'name'      => 'System',
                'email'     => null,
                // Random and thrown away. The account is never signed in to;
                // this exists only because the column is NOT NULL.
                'password'  => bcrypt(Str::random(40)),
                'user_type' => 'admin',
                'is_active' => false,
                'can_login' => false,
            ]
        )->id;
    }

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
    /**
     * How long a customer may keep the temporary password they were issued.
     *
     * A constant rather than a System Setting: it is quoted verbatim in the
     * credentials message the customer receives, so the two cannot be allowed
     * to drift apart by someone editing a settings screen.
     */
    public const TEMPORARY_PASSWORD_DAYS = 3;

    /**
     * A temporary password that is safe to send and realistic to type.
     *
     * Always contains an upper case letter, a lower case letter and a digit,
     * built one class at a time rather than hoping a random draw covers all
     * three -- otherwise the occasional password fails a mixed-case policy and
     * the customer is told their own credentials are invalid.
     *
     * 0/O and 1/l/I are left out on purpose. This is read off an SMS and typed
     * by hand, and those are the characters that turn into a support call.
     *
     * random_int() throughout, including the shuffle: shuffle() and rand() use
     * Mt19937, which is predictable from a handful of outputs, and this is a
     * live credential rather than a display value.
     *
     * Eight characters: short enough to read off a text and type, and the
     * floor of the password rules elsewhere in the app (min:8). Three of the
     * eight are spoken for by the one-per-class guarantee, so a shorter length
     * than that would silently drop a class -- hence the guard below.
     */
    public static function generateTemporaryPassword(int $length = 8): string
    {
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower  = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $all    = $upper . $lower . $digits;

        $pick = static fn (string $pool): string => $pool[random_int(0, strlen($pool) - 1)];

        $chars = [$pick($upper), $pick($lower), $pick($digits)];

        // Asking for fewer than one of each would return a password shorter
        // than the caller requested, which is worse than refusing.
        $length = max($length, count($chars));

        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $pick($all);
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    /**
     * Locked by passwords:lock-expired rather than by a person.
     *
     * Only such a lock is lifted when the owner finally sets their own
     * password. An account an admin deactivated stays deactivated.
     */
    public function isLockedForExpiredPassword(): bool
    {
        return $this->password_locked_at !== null;
    }

    /**
     * Still signed in with a password somebody else issued, past its deadline.
     */
    public function temporaryPasswordExpired(): bool
    {
        return $this->password_changed_at === null
            && $this->password_expires_at !== null
            && $this->password_expires_at->isPast();
    }

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
        // So a client can count the days down rather than only discovering the
        // deadline by being refused on the fourth morning.
        $array['temporary_password_expired'] = $this->temporaryPasswordExpired();

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
