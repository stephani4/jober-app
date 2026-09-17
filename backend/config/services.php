<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // HTTP API VK Карт: обратное геокодирование адресов точек заказа.
    'vk_maps' => [
        'key' => env('VK_MAPS_API_KEY'),
        'base_url' => env('VK_MAPS_BASE_URL', 'https://maps.vk.com/api'),
        // Проверка TLS-сертификата VK API. Выключать только локально, если у PHP нет CA-бандла.
        'verify' => env('VK_MAPS_HTTP_VERIFY', true),
    ],

];
