<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Ensures only customer users can access customer routes
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        /** @var \App\Models\User $user */
        $user = $guard->user();

        // CRITICAL: Verify user_type from token claims
        $tokenUserType = $guard->payload()->get('user_type');

        if ($tokenUserType !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Customer authentication required.',
                'user_type_in_token' => $tokenUserType
            ], 403);
        }

        // Double-check database user_type
        if ($user->user_type !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Customer account required.'
            ], 403);
        }

        // Verify user is active
        if (!$user->canLogin()) {
            return response()->json([
                'success' => false,
                'message' => 'Account deactivated'
            ], 403);
        }

        // No borrower behind the account, no portal.
        //
        // ResolvesAuthenticatedCustomerTrait::myCustomer() raises this as a 404
        // too, but it does so with abort() from inside each controller's
        // blanket `catch (\Throwable)`, which caught the HttpException along
        // with everything else and turned a deliberate 404 into a 500 carrying
        // the internal message. Deciding it here, before any controller runs,
        // means the answer is the intended one on every /my route at once.
        if (!$user->customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customer profile not found.',
            ], 404);
        }

        return $next($request);
    }
}
