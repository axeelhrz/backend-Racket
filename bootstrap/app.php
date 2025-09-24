<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add CORS middleware to API routes, but remove EnsureFrontendRequestsAreStateful
        // for token-based authentication
        $middleware->api(prepend: [
            \App\Http\Middleware\HandleCors::class,
        ]);

        // Only apply Sanctum's stateful middleware to web routes that need it
        $middleware->web(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'cors' => \App\Http\Middleware\HandleCors::class,
        ]);

        // Disable CSRF validation for API routes - Sanctum handles this with tokens
        $middleware->validateCsrfTokens(except: [
            'api/*', // Disable Laravel's default CSRF for all API routes
            'tournaments/*', // Also exclude tournament routes
            'registro-rapido',
            'registro-rapido/*',
            'sanctum/csrf-cookie',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle CORS-related exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->is('api/*') && $e->getStatusCode() === 405) {
                return response()->json([
                    'message' => 'Method not allowed',
                    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH']
                ], 405)
                ->header('Access-Control-Allow-Origin', $request->header('Origin', '*'))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
                ->header('Access-Control-Allow-Credentials', 'true');
            }
        });
    })->create();