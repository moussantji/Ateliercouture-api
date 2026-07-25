<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_ids' => array_filter(array_map('trim', explode(',', env('GOOGLE_CLIENT_IDS', env('GOOGLE_CLIENT_ID', ''))))),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
];
