<?php

return [
    // Afdelingen die een vestiging (org_depots.departments) kan hebben — vaste lijst, slug => label.
    // Child-apps lezen ze via /api/infrastructure (o.a. schade-app: geen servicedesk → werkplaats neemt over).
    'afdelingen' => [
        'binnendienst' => 'Binnendienst',
        'expeditie' => 'Expeditie',
        'werkplaats' => 'Werkplaats',
        'servicedesk' => 'Servicedesk (service)',
        'fleet' => 'Fleet',
        'projecten' => 'Projecten',
    ],

    'brand' => [
        'name' => 'Boels',
        'product' => 'CORE',
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

    // Chat-pop-up: ontvangers krijgen een venster midden in beeld zodra een
    // van de afzenders hun een chatbericht stuurt (alleen deze combinatie).
    'chat_popup' => [
        'ontvangers' => ['lisanne.rodrigues@boels.nl'],
        'afzenders'  => ['w_groeneweg@hotmail.com', 'wim.groeneweg@boels.nl'],
    ],
];
