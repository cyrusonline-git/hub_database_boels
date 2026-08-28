<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert het BP-klantportaal (bp.boels.sorai.nl) als child-app voor
 * de BOELS-kant. Twee Boels-rollen: superadmin (Boels Admin in beide
 * versies) en boels-medewerker (alleen in het nieuwe /v2-portaal).
 * Klantrollen (BP Admin/Manager/Medewerker) blijven volledig in de app.
 * Tegel in BP-groen (klantportaal) naar de hoofdapp.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'bp')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'BP Portaal',
                'slug'        => 'bp',
                'description' => 'Huur- en bestelportaal BP (klantportaal)',
                'url'         => 'https://bp.boels.sorai.nl',
                'icon'        => 'bi-fuel-pump',
                'color'       => '#009639',
                'sort_order'  => 67,
                'active'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['superadmin',       'Superadmin (Boels)', 'Boels Admin in het BP-portaal (hoofdapp én /v2)'],
            ['boels-medewerker', 'Boels Medewerker',   'Boels-medewerker in het nieuwe BP-portaal (/v2)'],
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
        $appId = DB::table('applications')->where('slug', 'bp')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
