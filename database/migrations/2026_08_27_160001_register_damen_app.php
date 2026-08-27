<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert het Damen-klantportaal (damen.sorai.nl) als child-app voor
 * de BOELS-kant: rollen superadmin en binnendienst (klantrollen blijven
 * volledig in de app zelf). Testfase: URL /v2 en tegel inactief.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'damen')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Damen Scan',
                'slug'        => 'damen',
                'description' => 'Scan- en retourportaal Damen (klantportaal)',
                'url'         => 'https://damen.sorai.nl/v2',
                'icon'        => 'bi-qr-code-scan',
                'color'       => '#FF6600',
                'sort_order'  => 65,
                'active'      => false, // testfase
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['superadmin',   'Superadmin (Boels)', 'Volledig beheer van het Damen-portaal'],
            ['binnendienst', 'Binnendienst',       'Binnendienst-dashboard van het Damen-portaal'],
        ];
        foreach ($rollen as [$slug, $name, $beschrijving]) {
            $rolId = DB::table('roles')->where('application_id', $appId)
                ->where('slug', $slug)->whereNull('deleted_at')->value('id');
            if (! $rolId) {
                $rolId = DB::table('roles')->insertGetId([
                    'name'           => $name,
                    'slug'           => $slug,
                    'description'    => $beschrijving,
                    'is_system'      => false,
                    'application_id' => $appId,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
            $link = DB::table('application_role')
                ->where('application_id', $appId)->where('role_id', $rolId)->exists();
            if (! $link) {
                DB::table('application_role')->insert([
                    'application_id' => $appId,
                    'role_id'        => $rolId,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'damen')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
