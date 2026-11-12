<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
        'web_client_secret' => env('GOOGLE_WEB_CLIENT_SECRET'),
        'redirect_uris' => array_filter(array_map('trim', explode(',', (string) env('GOOGLE_REDIRECT_URIS', '')))),
    ],
];