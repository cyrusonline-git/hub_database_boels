<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard-rollen zoals Wim ze wil (12-11-2026): één beheerdersrol en per
 * functie een rol (iedereen ziet zijn eigen gegevens; de rol bepaalt het
 * overzicht). De voorlopige rollen 'medewerker' en 'manager' vervallen als
 * er nog niemand aan gekoppeld is.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $appId = DB::table('applications')->where('slug', 'dashboard')->value('id');
        if (! $appId) {
            return;
        }

        $rollen = [
            ['admin',        'Beheerder',     'Beheer van koppelingen en instellingen; ziet alles'],
            ['binnendienst', 'Binnendienst',  'Eigen overzicht voor de binnendienst'],
            ['werkplaats',   'Werkplaats',    'Eigen overzicht voor de werkplaats'],
            ['servicedesk',  'Servicedesk',   'Eigen overzicht voor de servicedesk'],
            ['expeditie',    'Expeditie',     'Eigen overzicht voor de expeditie'],
            ['area-manager', 'Area Manager',  'Eigen overzicht plus het team in de eigen area'],
            ['bu-manager',   'BU Manager',    'Eigen overzicht plus de hele business unit'],
            ['vlootmanager', 'Vlootmanager',  'Eigen overzicht voor vlootbeheer'],
        ];

        foreach ($rollen as [$slug, $name, $beschrijving]) {
            $bestaat = DB::table('roles')
                ->where('application_id', $appId)->where('slug', $slug)
                ->whereNull('deleted_at')->exists();
            if (! $bestaat) {
                DB::table('roles')->insert([
                    'name' => $name, 'slug' => $slug, 'description' => $beschrijving,
                    'is_system' => false, 'application_id' => $appId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Voorlopige rollen opruimen (alleen als niemand ze heeft)
        foreach (['medewerker', 'manager'] as $oud) {
            $rolId = DB::table('roles')->where('application_id', $appId)->where('slug', $oud)->whereNull('deleted_at')->value('id');
            if ($rolId && ! DB::table('user_roles')->where('role_id', $rolId)->exists()) {
                DB::table('roles')->where('id', $rolId)->update(['deleted_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Bewust leeg: rollen blijven staan.
    }
};
