<?php

use Laravel\Sanctum\Sanctum;

return [
    /*
    | Stateful domains: hosts die session-based auth gebruiken (cookies)
    | i.p.v. tokens. Voor cross-subdomein SSO zetten we alle *.sorai.nl
    | child-apps hier in.
    */
    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1,databasehub.sorai.nl,hub.sorai.nl',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
