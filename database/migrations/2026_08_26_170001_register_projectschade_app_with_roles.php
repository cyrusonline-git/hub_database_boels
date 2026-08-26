<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de Projectschade-app (projectschade.sorai.nl) als child-app,
 * inclusief haar app-rollen (o.a. werkplaats — nieuw in CORE, per app
 * gescheiden dus botst nergens mee), zodat /api/access/projectschade werkt.
 *
 * - Testfase: URL wijst naar /v2 en de tegel staat op inactief; bij
 *   livegang de URL op de root zetten en de app activeren.
 * - Rol-slugs met streepjes (area-manager), conform de CORE-conventie;
 *   de app vertaalt naar underscores.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'projectschade')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Boels Projectschade',
                'slug'        => 'projectschade',
                'description' => 'Schade & Retour Registratie',
                'url'         => 'https://projectschade.sorai.nl/v2',
                'icon'        => 'bi-cone-striped',
                'color'       => '#FF6600',
                'sort_order'  => 55,
                'active'      => false, // testfase: tegel nog niet tonen
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['superadmin',   'Superadmin (Boels)', 'Volledig beheer: app-instellingen en toegangsbeheer'],
            ['admin',        'Beheerder',          'Beperkt beheer: alleen lokale accounts'],
            ['expeditie',    'Expeditie',          'Schades melden en retourafhandeling'],
            ['servicedesk',  'Servicedesk',        'Schadebeoordeling en onderdelenbeheer'],
            ['binnendienst', 'Binnendienst',       'Doorbelasting en klantcommunicatie'],
            ['werkplaats',   'Werkplaats',         'Reparaties en werkplaatsafhandeling'],
            ['area-manager', 'Area Manager',       'Overzichten van alle depots in de eigen area'],
        ];

        foreach ($rollen as [$slug, $name, $beschrijving]) {
            $bestaat = DB::table('roles')
                ->where('application_id', $appId)->where('slug', $slug)
                ->whereNull('deleted_at')->exists();
            if (! $bestaat) {
                DB::table('roles')->insert([
                    'name'           => $name,
                    'slug'           => $slug,
                    'description'    => $beschrijving,
                    'is_system'      => false,
                    'application_id' => $appId,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'projectschade')->value('id');
        if ($appId) {
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
