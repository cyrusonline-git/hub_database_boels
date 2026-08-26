<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Livegang inhuur-app: URL van /v2 (testfase) naar de live root, tegel
 * activeren, en de app-rollen aanvinken voor de launcher zodat iedereen
 * met een Inhuur-rol de tegel op het dashboard ziet.
 */
return new class extends Migration
{
    public function up(): void
    {
        $appId = DB::table('applications')->where('slug', 'inhuur')->value('id');
        if (! $appId) {
            return; // registratie-migratie is dan nog niet gedraaid
        }

        DB::table('applications')->where('id', $appId)->update([
            'url'        => 'https://boels.sorai.nl',
            'active'     => true,
            'updated_at' => now(),
        ]);

        // Launcher-zichtbaarheid: elke app-rol van Inhuur toont de tegel
        $roleIds = DB::table('roles')->where('application_id', $appId)
            ->whereNull('deleted_at')->pluck('id');
        foreach ($roleIds as $roleId) {
            $bestaat = DB::table('application_role')
                ->where('application_id', $appId)->where('role_id', $roleId)->exists();
            if (! $bestaat) {
                DB::table('application_role')->insert([
                    'application_id' => $appId,
                    'role_id'        => $roleId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'inhuur')->value('id');
        if ($appId) {
            DB::table('applications')->where('id', $appId)->update([
                'url'    => 'https://boels.sorai.nl/v2',
                'active' => false,
            ]);
            DB::table('application_role')->where('application_id', $appId)->delete();
        }
    }
};
