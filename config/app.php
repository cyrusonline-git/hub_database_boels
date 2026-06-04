<?php

return [
    'name' => env('APP_NAME', 'Boels CORE Platform'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://databasehub.sorai.nl'),
    'timezone' => env('APP_TIMEZONE', 'Europe/Amsterdam'),
    'locale' => env('APP_LOCALE', 'nl'),
    'fallback_locale' => 'en',
    'faker_locale' => 'nl_NL',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
