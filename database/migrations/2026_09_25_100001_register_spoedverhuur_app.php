<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de child-app "Spoedverhuur" (dienstrooster, ruilen, meldingen,
 * vergoedingen) met haar rollen, zodat de tegel in de launcher verschijnt en
 * /api/access/spoedverhuur de rollen teruggeeft.
 */
return new class extends Migration
{
    private array $rollen = [
        ['medewerker', 'Medewerker', 'Draait spoedverhuur-/storingsdiensten: rooster inzien, eigen diensten, ruilen'],
        ['manager', 'Manager', 'Overzichten: bezetting, ruilingen, belasting, maandoverzicht'],
        ['admin', 'Beheerder', 'Rooster importeren/bewerken, medewerkers koppelen, mails, instellingen, maandoverzicht versturen'],
    ];

    public function up(): void
    {
        $now = now();
        $appId = DB::table('applications')->where('slug', 'spoedverhuur')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name' => 'Spoedverhuur',
                'slug' => 'spoedverhuur',
                'description' => 'Dienstrooster spoedverhuur en storingsdiensten: inzien, ruilen, meldingen en maandelijkse vergoedingen.',
                'url' => 'https://spoedverhuur.sorai.nl',
                'icon' => 'bi-telephone-forward',
                'color' => '#FF6600',
                'sort_order' => 72,
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
        $appId = DB::table('applications')->where('slug', 'spoedverhuur')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
