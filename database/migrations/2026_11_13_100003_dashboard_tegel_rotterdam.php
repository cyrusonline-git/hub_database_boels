<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard-tegel aanzetten in CORE, voorlopig alleen voor depot Rotterdam (Wim 12-11-2026),
 * en de acht dashboard-rollen aan de tegel koppelen zodat iedereen met zo'n rol de tegel ziet.
 */
return new class extends Migration
{
    public function up(): void
    {
        $appId = DB::table('applications')->where('slug', 'dashboard')->value('id');
        if (! $appId) {
            return;
        }
        DB::table('applications')->where('id', $appId)->update([
            'active' => true,
            'restricted_to_depots' => json_encode(['Rotterdam']),
            'updated_at' => now(),
        ]);
        $rolIds = DB::table('roles')->where('application_id', $appId)->whereNull('deleted_at')->pluck('id');
        foreach ($rolIds as $rolId) {
            $bestaat = DB::table('application_role')->where('application_id', $appId)->where('role_id', $rolId)->exists();
            if (! $bestaat) {
                DB::table('application_role')->insert(['application_id' => $appId, 'role_id' => $rolId]);
            }
        }
    }

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'dashboard')->value('id');
        if ($appId) {
            DB::table('applications')->where('id', $appId)->update(['active' => false, 'restricted_to_depots' => null]);
        }
    }
};
