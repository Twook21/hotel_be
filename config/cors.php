<?php

return [
    'paths' => ['api/*', 'v1/*', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // Untuk development, bisa lebih spesifik untuk production
    // Atau gunakan:
    // 'allowed_origins' => [
    //     'http://localhost:3000',
    //     'http://localhost:*', // Untuk Flutter web
    //     'http://127.0.0.1:*',
    // ],

    'allowed_origins_patterns' => [
        'http://localhost:*',
        'http://127.0.0.1:*',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
