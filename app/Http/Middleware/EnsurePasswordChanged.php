<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Hold a user at the door until they have set a password of their own.
 *
 * `password_changed_at` has been on the users table all along and nothing ever
 * read it, so an account created with a password someone else chose kept that
 * password for as long as it existed. Two of the seeded staff accounts were
 * still signed in with their own NIC -- a number printed on the customer,
 * guarantor and document screens for any officer to read.
 *
 * Stamping the column is what clears the block, and the password-change
 * endpoint does that. Everything a blocked user needs in order to get past
 * this is on the allow list below; nothing else is reachable.
 */
class EnsurePasswordChanged
{
    /**
     * Routes a user must still reach while blocked: the change itself, the
     * identity call the frontend makes on boot, and the way out.
     */
    private const ALLOWED = [
        'api/v1/password/change',
        'api/v1/password/request-change',
        'api/v1/password/change-with-otp',
        'api/v1/me',
        'api/v1/logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user->password_changed_at !== null) {
            return $next($request);
        }

        foreach (self::ALLOWED as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Set your own password before using the system.',
            'errors'  => ['password_change_required' => true],
        ], 403);
    }
}
