<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'], // Your frontend
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Disposition'], // Needed for file downloads
    'max_age' => 0,
    'supports_credentials' => true, // If you send cookies or auth headers
];
