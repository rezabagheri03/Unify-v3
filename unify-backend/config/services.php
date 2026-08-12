<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pushe - Iranian push notifications (Android intranet)
    |--------------------------------------------------------------------------
    */
    'pushe' => [
        'api_key' => env('PUSHE_API_KEY'),
        'app_id' => env('PUSHE_APP_ID'),
        // TODO-031/D-006: push delivery is wired but OFF unless explicitly
        // enabled after the Pushe account + device-token flow are verified
        // against the real app_ids. Prevents a silently-dead integration.
        'enabled' => env('PUSHE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kavenegar - Iranian SMS (optional critical alerts)
    |--------------------------------------------------------------------------
    */
    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'sender' => env('KAVENEGAR_SENDER', '10004346'),
        // D-006: SMS stays disabled until an actual trigger flow (e.g. staff
        // credential delivery) is approved by product — no silent dead code.
        'enabled' => env('KAVENEGAR_ENABLED', false),
    ],

];
