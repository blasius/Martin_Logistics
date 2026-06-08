<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers.
    |
    */

    // Include the base 'portal/login' and 'user' routes just in case
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'portal/login',
        'logout',
        'user'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Application URL (set APP_URL in your .env for production)
        env('APP_URL', 'https://martin-logistics.test'),
        'https://martin-logistics.test',
        'http://martin-logistics.test',

        // Vite Dev Server (Standard ports)
        'http://localhost:5173',
        'https://localhost:5173',
        'http://127.0.0.1:5173',
        'https://127.0.0.1:5173',

        // The specific Vite host from your .env
        env('VITE_DEV_SERVER_HOST'),
        'https://vite.martin-logistics.test',
        // PRODUCTION
        'https://martin-logistics.nova.bi',
        'https://www.martin-logistics.nova.bi',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // CRITICAL: This must be true to allow cookies/sessions
    'supports_credentials' => true,

];
