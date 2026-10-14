<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Exceptions\UnauthorizedException as PermissionDeniedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class,
            'jwt.auth' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class,
            'jwt.refresh' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'admin.auth' => \App\Http\Middleware\AdminAuthMiddleware::class,
            'customer.auth' => \App\Http\Middleware\CustomerAuthMiddleware::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'core.key' => \App\Http\Middleware\VerifyCdpCoreKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Handle JWT / Auth exceptions (unauthenticated)
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'unauthenticated',
                    'message' => 'Please sign in to continue.',
                ], 401);
            }
        });

        // JWT middleware failures (blacklisted / expired / invalid / missing
        // token) arrive as UnauthorizedHttpException. Without this they fall
        // through to the default handler and, in debug, dump a full stack
        // trace. `code` lets the frontend tell "session ended" from a real
        // auth problem.
        $exceptions->render(function (UnauthorizedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $reason = strtolower($e->getMessage());
                $code = match (true) {
                    str_contains($reason, 'blacklist') => 'token_blacklisted',
                    str_contains($reason, 'expired')   => 'token_expired',
                    str_contains($reason, 'invalid')   => 'token_invalid',
                    str_contains($reason, 'not provided'), str_contains($reason, 'absent') => 'token_missing',
                    default                            => 'unauthenticated',
                };

                $message = match ($code) {
                    'token_missing'     => 'Please sign in to continue.',
                    'token_expired'     => 'Your session has expired. Please sign in again to continue.',
                    'token_blacklisted' => 'You have been signed out. Please sign in again to continue.',
                    default             => 'Your session is no longer valid. Please sign in again.',
                };

                return response()->json([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => $message,
                ], 401);
            }
        });

        // 403: signed in, but this account is not allowed to do this.
        //
        // Spatie's UnauthorizedException says "User does not have the right
        // permissions." and, in debug, lists the permission names -- neither
        // is something an officer should see. One plain sentence, plus the
        // permission names under `required` so an admin can grant them.
        $forbidden = function (\Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $required = [];
            if ($e instanceof PermissionDeniedException) {
                $required = $e->getRequiredPermissions() ?: $e->getRequiredRoles();
            }

            return response()->json([
                'status'   => 'error',
                'code'     => 'forbidden',
                'message'  => "You don't have permission to do this. If you need access, please ask your administrator.",
                'required' => array_values($required),
            ], 403);
        };
        $exceptions->render(fn (PermissionDeniedException $e, Request $request) => $forbidden($e, $request));
        $exceptions->render(fn (AuthorizationException $e, Request $request) => $forbidden($e, $request));
        $exceptions->render(fn (AccessDeniedHttpException $e, Request $request) => $forbidden($e, $request));

        // Convert validation errors to JSON for APIs
        $exceptions->render(function (ValidationException $e) {
            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

        // Catch-all exception format for API
        $exceptions->render(function (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Token error',
                    'error' => $e->getMessage()
                ], 401);
            }
        });
    })->create();
