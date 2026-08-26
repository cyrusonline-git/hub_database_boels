<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de Boels Inhuurapplicatie (boels.sorai.nl) als child-app,
 * inclusief haar app-rollen, zodat /api/access/inhuur werkt en rollen in
 * Beheer → Applicaties/Gebruikers toegekend kunnen worden.
 *
 * - Testfase: URL wijst naar /v2/ en de tegel staat op inactief; bij
 *   livegang in Beheer → Applicaties de URL op de root zetten en de app
 *   activeren.
 * - Rol-slugs met streepjes (area-manager), conform hoe CORE ze zelf zou
 *   aanmaken bij "rollen importeren"; de app vertaalt naar underscores.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'inhuur')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Boels Inhuur',
                'slug'        => 'inhuur',
                'description' => 'Inhuur van materieel: aanvragen, dashboard en afmelden',
                'url'         => 'https://boels.sorai.nl/v2',
                'icon'        => 'bi-truck',
                'color'       => '#FF6600',
                'sort_order'  => 50,
                'active'      => false, // testfase: tegel nog niet tonen
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['superadmin',    'Superadmin (Boels)', 'Volledig beheer: app-instellingen en toegangsbeheer'],
            ['admin',         'Beheerder',          'Beperkt beheer: alleen lokale accounts en de gewone overzichten'],
            ['binnendienst',  'Binnendienst',       'Inhuur aanvragen, verwerken en afmelden'],
            ['expeditie',     'Expeditie',          'Transport en leveringen'],
            ['area-manager',  'Area Manager',       'Dashboard en rapportages van de eigen area'],
            ['bu-manager',    'BU Manager',         'Dashboard en rapportages van de hele BU'],
            ['fleetmanager',  'Fleetmanager',       'Vlootbeheer en rapportages'],
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
        $appId = DB::table('applications')->where('slug', 'inhuur')->value('id');
        if ($appId) {
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
