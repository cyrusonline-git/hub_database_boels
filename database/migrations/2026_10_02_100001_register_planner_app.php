<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de child-app "Transportplanner" (planning.sorai.nl) met haar
 * rollen, zodat de tegel in de launcher verschijnt en /api/access/planner de
 * rollen teruggeeft. De planner vertaalt elke rol-slug naar zijn eigen rol.
 */
return new class extends Migration
{
    private array $rollen = [
        ['planner', 'Planner', 'Ritten plannen op het planbord, chauffeurs en voertuigen aansturen, instellingen'],
        ['binnendienst', 'Binnendienst', 'Transportaanvragen indienen en volgen'],
        ['manager', 'Manager', 'Inzicht: statistieken, rapportage, uitzonderingen'],
        ['administratie', 'Administratie', 'Doorbelasting en facturatie'],
        ['chauffeur', 'Chauffeur', 'Eigen ritten van vandaag, navigatie, afronden (chauffeursapp)'],
    ];

    public function up(): void
    {
        $now = now();
        $appId = DB::table('applications')->where('slug', 'planner')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name' => 'Transportplanner',
                'slug' => 'planner',
                'description' => 'Transportritten plannen (bezorgen, ophalen, wisselen): planbord, chauffeursapp, klant-tracking, materieel en klanten uit CORE.',
                'url' => 'https://planning.sorai.nl',
                'icon' => 'bi-truck',
                'color' => '#FF6600',
                'sort_order' => 73,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        foreach ($this->rollen as [$slug, $naam, $beschrijving]) {
            $roleId = DB::table('roles')->where('application_id', $appId)->where('slug', $slug)->whereNull('deleted_at')->value('id');
            if (! $roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $naam, 'slug' => $slug, 'description' => $beschrijving, 'is_system' => false,
                    'application_id' => $appId, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $bestaat = DB::table('application_role')->where('application_id', $appId)->where('role_id', $roleId)->exists();
            if (! $bestaat) {
                DB::table('application_role')->insert(['application_id' => $appId, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $appId = DB::table('applications')->where('slug', 'planner')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
