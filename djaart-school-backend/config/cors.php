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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // CORS_ALLOWED_ORIGINS : liste separee par des virgules (ex. accès LAN
    // en plus de localhost) — distinct de FRONTEND_URL (unique, utilisé
    // pour les liens dans les e-mails).
    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173')))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
