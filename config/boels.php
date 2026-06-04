<?php

return [
    'brand' => [
        'name' => 'Boels',
        'product' => 'CORE Platform',
        'color' => env('BOELS_BRAND_COLOR', '#FF6600'),
        'text_color' => env('BOELS_BRAND_TEXT_COLOR', '#FFFFFF'),
    ],

    'superadmin' => [
        'name' => env('SUPERADMIN_NAME'),
        'email' => env('SUPERADMIN_EMAIL'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],

    'import' => [
        'storage_disk' => 'local',
        'storage_path' => 'imports',
        'max_file_size_mb' => 25,
    ],

    'audit' => [
        'enabled' => true,
        'log_views' => false,
    ],
];
