<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert Shell Rental Management (shell.sorai.nl, klantportaal) als child-app voor
 * de BOELS-kant. Twee Boels-rollen: superadmin (Boels Beheerder) en
 * boels-medewerker.
 * Klantrollen (Shell Beheerder/Manager/Medewerker) blijven volledig in de app.
 * Tegel in Shell-rood (klantportaal).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'shell')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Shell Rental Management',
                'slug'        => 'shell',
                'description' => 'Huur- en bestelportaal Shell (klantportaal)',
                'url'         => 'https://shell.sorai.nl',
                'icon'        => 'bi-fuel-pump',
                'color'       => '#DD1D21',
                'sort_order'  => 68,
                'active'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['superadmin',       'Superadmin (Boels)', 'Boels Beheerder in Shell Rental Management'],
            ['boels-medewerker', 'Boels Medewerker',   'Boels-medewerker in Shell Rental Management'],
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
        $appId = DB::table('applications')->where('slug', 'shell')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
