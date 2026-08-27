<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de Bulkretour-app (bulkretour.sorai.nl): tegel + één rol
 * 'melder' met launcher-vinkje. De app zelf controleert geen rollen
 * (iedere CORE-medewerker mag melden) — de rol bepaalt alleen wie de
 * tegel op het dashboard ziet.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $appId = DB::table('applications')->where('slug', 'bulkretour')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Bulkretour',
                'slug'        => 'bulkretour',
                'description' => 'Bulk-retouren melden aan de binnendienst',
                'url'         => 'https://bulkretour.sorai.nl',
                'icon'        => 'bi-arrow-return-left',
                'color'       => '#FF6600',
                'sort_order'  => 60,
                'active'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $rolId = DB::table('roles')->where('application_id', $appId)
            ->where('slug', 'melder')->whereNull('deleted_at')->value('id');
        if (! $rolId) {
            $rolId = DB::table('roles')->insertGetId([
                'name'           => 'Bulkretour melden',
                'slug'           => 'melder',
                'description'    => 'Ziet de Bulkretour-tegel en kan bulk-retouren melden',
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

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'bulkretour')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
