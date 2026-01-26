<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'], // allow all HTTP methods

    'allowed_origins' => ['*'], // allow all domains (change in production!)

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // allow all headers

    'exposed_headers' => ['Content-Disposition'], // for downloads

    'max_age' => 0,

    'supports_credentials' => false, // set to true if using cookies/auth
];
