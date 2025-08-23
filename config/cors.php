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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'registro-rapido'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3002', // Added for current frontend port
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3002', // Added for current frontend port
        'https://involved-retailers-occasionally-macintosh.trycloudflare.com',
        env('FRONTEND_URL', 'http://localhost:3000'),
        // Agregar tu dominio de producción aquí
        env('APP_URL', 'http://localhost'),
    ],

    'allowed_origins_patterns' => [
        'https://*.vercel.app',
        'https://*.vercel.com',
        'https://*.netlify.app',
        'https://*.netlify.com',
        'https://*.ngrok-free.app',
        'https://*.trycloudflare.com',
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
    ],

    'exposed_headers' => [
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];