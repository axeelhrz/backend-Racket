<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie', 
        'registro-rapido',
        'tournaments/*',
        'tournaments/*/participants',
        'tournaments/*/available-members',
        'tournaments/*/matches',
        'tournaments/*/matches/*',
        'tournaments/*/matches/*/result',
        'tournaments/*/bracket',
        'tournaments/*/generate-bracket',
        'matches/*',
        'matches/*/result',
        'matches/*/score'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3002', // Added for current frontend port
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3002', // Added for current frontend port
        'https://involved-retailers-occasionally-macintosh.trycloudflare.com',
        'https://raquet-power2-0.vercel.app', // Add your specific Vercel domain
        'https://web-production-40b3.up.railway.app', // Add your Railway domain
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('APP_URL', 'http://localhost'),
    ],

    'allowed_origins_patterns' => [
        'https://*.vercel.app',
        'https://*.vercel.com',
        'https://*.netlify.app',
        'https://*.netlify.com',
        'https://*.ngrok-free.app',
        'https://*.trycloudflare.com',
        'https://*.up.railway.app', // Add Railway pattern
        // Patrón para subdominios de tu dominio
        'https://*.tudominio.com',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'Origin',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
    ],

    'exposed_headers' => [
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];