<?php
return [

    'paths' => ['api/*', 'storage/*'], // Include the /storage path

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
        'https://api.fitnesspoint.rw'
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/aapi\.fitnesspoint\.rw$/',
        '/^https:\/\/fitnesspoint\.rw$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
