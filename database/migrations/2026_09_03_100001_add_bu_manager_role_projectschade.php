<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nieuwe app-rol "BU Manager" voor de Projectschade-app: landelijk
 * read-only dashboard (alle areas en depots), tegenover de Area Manager
 * die alleen de eigen area ziet. Dash-slug conform de CORE-conventie;
 * de app vertaalt naar bu_manager. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $appId = DB::table('applications')->where('slug', 'projectschade')->value('id');
        if (! $appId) {
            return; // app nog niet geregistreerd (registratie-migratie draait eerst)
        }

        $bestaat = DB::table('roles')
            ->where('application_id', $appId)->where('slug', 'bu-manager')
            ->whereNull('deleted_at')->exists();
        if (! $bestaat) {
            $now = now();
            DB::table('roles')->insert([
                'name'           => 'BU Manager',
                'slug'           => 'bu-manager',
                'description'    => 'Landelijk overzicht: alle areas en alle depots (read-only)',
                'is_system'      => false,
                'application_id' => $appId,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'projectschade')->value('id');
        if ($appId) {
            DB::table('roles')->where('application_id', $appId)->where('slug', 'bu-manager')->delete();
        }
    }
};
