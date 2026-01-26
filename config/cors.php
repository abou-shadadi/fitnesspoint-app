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

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    // ❗ EXPLICIT ORIGINS — NO *
    'allowed_origins' => [
        'http://localhost:3000',
        'https://fitnesspoint.rw',
    ],

    'allowed_origins_patterns' => [],

    // ❗ Explicit headers helps preflight
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
    ],

    // ❗ REQUIRED for downloads
    'exposed_headers' => [
        'Content-Disposition',
        'Content-Type',
        'Content-Length',
    ],

    'max_age' => 0,

    'supports_credentials' => false,
];
