<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Http\ApiResponse;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - applied to all requests
        $middleware->append(\App\Http\Middleware\RequestId::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(SanitizeInput::class);

        // Named middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'optional_sanctum' => \App\Http\Middleware\OptionalSanctum::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            // Laravel's ValidationException - let it handle itself
            if ($e instanceof ValidationException) {
                return null;
            }

            // Our custom domain exceptions - use their render method
            if ($e instanceof DomainException) {
                return $e->render();
            }

            // Eloquent model not found
            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::notFound('Resource not found');
            }

            // PHP's built-in DomainException (backward compatibility)
            if ($e instanceof \DomainException) {
                return ApiResponse::fromDomainException($e);
            }

            // Authentication failures
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return ApiResponse::unauthorized($e->getMessage() ?: 'Unauthenticated.');
            }

            // Authorization failures
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return ApiResponse::forbidden($e->getMessage() ?: 'This action is unauthorized.');
            }

            // HTTP 403 exceptions
            if ($e instanceof HttpException && $e->getStatusCode() === 403) {
                return ApiResponse::forbidden($e->getMessage() ?: 'This action is unauthorized.');
            }

            // Generic server errors
            return ApiResponse::serverError(
                config('app.debug') ? $e->getMessage() : 'Server Error'
            );
        });
    })->create();
