<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],

    // Native React Native requests do not use browser CORS. Web builds must
    // be explicitly listed through CORS_ALLOWED_ORIGINS in each environment.
    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:8081,http://localhost:19006,http://localhost:3000')),
    )),

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Origin'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
