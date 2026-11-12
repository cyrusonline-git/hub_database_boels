<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de Dashboard-app (dashboard.sorai.nl) als child-app met haar
 * app-rollen, zodat /api/access/dashboard werkt. Tegel nog inactief: de
 * inhoud van de app wordt nog bepaald; bij livegang 'active' op true zetten
 * in Beheer → Applicaties.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'dashboard')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Dashboard',
                'slug'        => 'dashboard',
                'description' => 'Overzicht per medewerker van openstaand werk in alle Boels-apps',
                'url'         => 'https://dashboard.sorai.nl',
                'icon'        => 'bi-speedometer2',
                'color'       => '#FF6600',
                'sort_order'  => 5,
                'active'      => false,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['medewerker', 'Medewerker', 'Ziet het eigen overzicht'],
            ['manager',    'Manager',    'Ziet daarnaast het overzicht van het eigen team (depot/area)'],
            ['admin',      'Beheerder',  'Beheer van koppelingen en instellingen'],
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
        $appId = DB::table('applications')->where('slug', 'dashboard')->value('id');
        if ($appId) {
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
