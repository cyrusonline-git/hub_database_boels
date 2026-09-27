<?php

/*
|--------------------------------------------------------------------------
| Boels Vraagbaak — eigen AI in CORE
|--------------------------------------------------------------------------
| Leest de databases van de gekoppelde apps READ-ONLY, op dezelfde server.
| Er gaat niets naar externe diensten. Paden zijn relatief aan de map
| <account>/domains (= twee niveaus boven de Laravel-root van CORE); een
| absolute map kan met VRAAGBAAK_DOMAINS_DIR worden afgedwongen (bv. lokaal).
*/

return [
    'domains_dir' => env('VRAAGBAAK_DOMAINS_DIR'),
    'cache_seconden' => (int) env('VRAAGBAAK_CACHE', 60),
    'max_rijen' => 200,

    // bron => [type, pad (t.o.v. domains_dir), app-slug in CORE (toegang), naam, url]
    'bronnen' => [
        'tankapp' => ['type' => 'sqlite', 'pad' => 'tankapp.sorai.nl/public_html/database/tankapp.db', 'slug' => 'tankapp', 'naam' => 'Tankapp', 'url' => 'https://tankapp.sorai.nl'],
        'inhuur' => ['type' => 'sqlite', 'pad' => 'boels.sorai.nl/public_html/data/boels_inhuur.sqlite', 'slug' => 'inhuur', 'naam' => 'Inhuur', 'url' => 'https://boels.sorai.nl'],
        'projectschade' => ['type' => 'sqlite', 'pad' => 'projectschade.sorai.nl/public_html/database.sqlite', 'slug' => 'projectschade', 'naam' => 'Projectschade', 'url' => 'https://projectschade.sorai.nl'],
        'scanner' => ['type' => 'sqlite', 'pad' => 'scanner.sorai.nl/public_html/data/scanner.db', 'slug' => 'scanner', 'naam' => 'Scanner', 'url' => 'https://scanner.sorai.nl'],
        'voorraad' => ['type' => 'sqlite', 'pad' => 'voorraad.sorai.nl/laravel_app/database/database.sqlite', 'slug' => 'voorraad', 'naam' => 'Voorraad tool', 'url' => 'https://voorraad.sorai.nl'],
        'spoedverhuur' => ['type' => 'sqlite', 'pad' => 'spoedverhuur.sorai.nl/laravel_app/database/database.sqlite', 'slug' => 'spoedverhuur', 'naam' => 'Spoedverhuur', 'url' => 'https://spoedverhuur.sorai.nl'],
        'shell' => ['type' => 'mysql_secrets', 'pad' => 'shell.sorai.nl/public_html/config/secrets.php', 'prefix' => 'shell_', 'slug' => 'shell', 'naam' => 'Shell-portaal', 'url' => 'https://shell.sorai.nl'],
    ],

    // Vaste synoniemen voor depots/areas (kleine letters) => CORE-naam of area-naam
    'depot_aliassen' => [
        'rdam' => 'Rotterdam', "r'dam" => 'Rotterdam', 'rotterdam' => 'Rotterdam', 'europoort' => 'Rotterdam', 'botlek' => 'Rotterdam',
        'chemelot' => 'Chemelot', 'geleen' => 'Chemelot', 'sittard' => 'Chemelot',
        'pernis' => 'Pernis', 'antwerpen' => 'Antwerpen', 'moerdijk' => 'Moerdijk', 'terneuzen' => 'Terneuzen',
        'delfzijl' => 'Delfzijl', 'eemshaven' => 'Eemshaven', 'vlissingen' => 'Vlissingen', 'amsterdam' => 'Amsterdam', 'ijmuiden' => 'IJmuiden',
    ],
    'area_aliassen' => ['west' => 'West', 'zuid' => 'Zuid', 'noord' => 'Noord', 'klantlocaties' => 'Klantlocaties', 'belgie' => 'Antwerpen', 'belgië' => 'Antwerpen'],

    // Tekst op de tegel en in de tool (Wim: intern is men huiverig voor AI buiten de deur)
    'banner' => 'Eigen AI van Boels Industrial. Draait op onze eigen server en rekent alleen in onze eigen applicatiedata. Er gaat niets naar externe AI-diensten.',
];
