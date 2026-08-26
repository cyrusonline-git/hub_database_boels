<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Testfase Projectschade: tegel zichtbaar maken (URL blijft /v2) en de
 * app-rollen aanvinken voor de launcher, zodat testers met een
 * Projectschade-rol de tegel op het CORE-dashboard zien.
 * Bij livegang hoeft dan alleen nog de URL naar de root.
 */
return new class extends Migration
{
    public function up(): void
    {
        $appId = DB::table('applications')->where('slug', 'projectschade')->value('id');
        if (! $appId) {
            return;
        }

        DB::table('applications')->where('id', $appId)->update([
            'active'     => true,
            'updated_at' => now(),
        ]);

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
        $appId = DB::table('applications')->where('slug', 'projectschade')->value('id');
        if ($appId) {
            DB::table('applications')->where('id', $appId)->update(['active' => false]);
            DB::table('application_role')->where('application_id', $appId)->delete();
        }
    }
};
