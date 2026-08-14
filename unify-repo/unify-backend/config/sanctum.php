<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. The SPA uses Bearer tokens, so this is only a
    | fallback for same-origin cookie flows.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,localhost:5173,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes (SEC-03 fix)
    |--------------------------------------------------------------------------
    |
    | Personal access tokens previously lived forever. They now expire 7 days
    | (10080 minutes) after creation; expired tokens are pruned daily via the
    | `sanctum:prune-expired` scheduled command. Login also stamps an explicit
    | per-token `expires_at` as a second enforcement layer.
    |
    */

    'expiration' => (int) env('SANCTUM_TOKEN_EXPIRATION_MINUTES', 10080),

    /*
    |--------------------------------------------------------------------------
    | Token Prefix / Middleware
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
