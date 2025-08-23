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
        // Add CORS middleware to API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\HandleCors::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        // Disable CSRF validation for API routes - Sanctum handles this
        $middleware->validateCsrfTokens(except: [
            'api/*', // Disable Laravel's default CSRF for all API routes
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