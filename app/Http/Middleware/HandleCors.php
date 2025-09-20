<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === "OPTIONS") {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-XSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers to the response
        return $response
            ->header('Access-Control-Allow-Origin', $this->getAllowedOrigin($request))
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN, X-XSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Get the allowed origin for the request
     */
    private function getAllowedOrigin(Request $request): string
    {
        $origin = $request->header('Origin');
        
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3002',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3002',
            'https://raquet-power2-0.vercel.app',
            'https://web-production-40b3.up.railway.app',
            env('FRONTEND_URL', 'http://localhost:3000'),
            env('APP_URL', 'http://localhost'),
        ];

        $allowedPatterns = [
            'https://*.vercel.app',
            'https://*.vercel.com',
            'https://*.netlify.app',
            'https://*.netlify.com',
            'https://*.up.railway.app',
            'https://*.trycloudflare.com',
        ];

        // Check exact matches first
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        // Check pattern matches
        foreach ($allowedPatterns as $pattern) {
            $regex = str_replace('*', '.*', preg_quote($pattern, '/'));
            if (preg_match('/^' . $regex . '$/', $origin)) {
                return $origin;
            }
        }

        // Default fallback
        return $allowedOrigins[0];
    }
}