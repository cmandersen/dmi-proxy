<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'dmi' => [
        'climate_key' => env('DMI_CLIMATE_KEY'),
        'metobs_key' => env('DMI_METOBS_KEY'),
        'forecast_key' => env('DMI_FORECAST_KEY'),
        'cache_ttl' => [
            'current' => env('DMI_CACHE_CURRENT', 300),    // 5 minutes
            'forecast' => env('DMI_CACHE_FORECAST', 1800), // 30 minutes
            'historical' => env('DMI_CACHE_HISTORICAL', 86400), // 24 hours
            'geocoding' => env('DMI_CACHE_GEOCODING', 86400), // 24 hours
        ],
    ],

];
