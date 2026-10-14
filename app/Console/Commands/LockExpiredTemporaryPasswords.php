<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Traits\ActivityLogTrait;
use Illuminate\Console\Command;

/**
 * Lock the accounts that never replaced the temporary password they were sent.
 *
 * A customer is issued a generated password and told to change it within
 * User::TEMPORARY_PASSWORD_DAYS days. This is what happens when they do not:
 * the account is marked locked and can_login is taken away, so it reads as
 * locked on every screen that lists users rather than only failing at the door.
 *
 * Enforcement does NOT depend on this command running. AuthController refuses
 * an expired temporary password at login time, computed from the deadline
 * itself, so a night when the scheduler does not fire cannot let an expired
 * password through. What this adds is the persisted state: a column an admin
 * can filter on, and a can_login flag the rest of the system already honours.
 *
 * password_locked_at is what distinguishes this from an admin switching an
 * account off by hand. Only an account this command locked is unlocked again
 * when its owner sets a new password -- a deliberate deactivation survives.
 */
class LockExpiredTemporaryPasswords extends Command
{
    use ActivityLogTrait;

    protected $signature = 'passwords:lock-expired';

    protected $description = 'Lock customer accounts whose temporary password was never changed within the allowed days.';

    public function handle(): int
    {
        $expired = User::query()
            // Still on the password they were issued.
            ->whereNull('password_changed_at')
            // Issued with a deadline, and it has passed. A null deadline is the
            // older staff case, which EnsurePasswordChanged blocks from the
            // first request and which this command has no business touching.
            ->whereNotNull('password_expires_at')
            ->where('password_expires_at', '<', now())
            // Locked once and only once. Without this the command would rewrite
            // password_locked_at every night and lose the date it happened.
            ->whereNull('password_locked_at')
            ->get();

        foreach ($expired as $user) {
            $user->forceFill([
                'password_locked_at' => now(),
                'can_login'          => false,
            ])->save();

            $this->line("  locked #{$user->id} {$user->username} (expired {$user->password_expires_at})");
        }

        $summary = "Accounts locked for an unchanged temporary password: {$expired->count()}.";
        $this->info($summary);

        $this->logActivity('UPDATE', 'User', $summary, [
            'locked'   => $expired->count(),
            'user_ids' => $expired->pluck('id')->all(),
        ]);

        return self::SUCCESS;
    }
}
