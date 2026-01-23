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

    'paths' => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie', 'api/sanctum/csrf-cookie', 'debug-*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'https://localhost',           // Capacitor with https scheme
        'http://localhost:5173',
        'http://localhost:3000',
        'https://star.iselcouno.com',
        'https://apistar.iselcouno.com', // Self
        'capacitor://localhost',  // Capacitor Android/iOS
        'ionic://localhost',      // Ionic WebView
        'http://localhost:8100',  // Ionic serve
    ],

    'allowed_origins_patterns' => [
        '/^capacitor:\/\/.*/',    // Any capacitor origin
        '/^https?:\/\/localhost(:.*)?$/', // Any localhost (http or https, any port)
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
