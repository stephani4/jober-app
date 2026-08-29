<?php

return [
    'url' => env('CENTRIFUGO_URL', 'http://centrifugo:8000'),
    'api_key' => env('CENTRIFUGO_API_KEY', ''),
    'token_hmac_secret' => env('CENTRIFUGO_TOKEN_HMAC_SECRET', ''),
    'token_ttl' => (int) env('CENTRIFUGO_TOKEN_TTL', 3600),
    'proxy_secret' => env('CENTRIFUGO_PROXY_SECRET', ''),
    'channels' => [
        'search' => 'orders:search',
        'personal_prefix' => 'personal:',
    ],
];
