<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de child-app "Servicedesk" (Parts System, servicedesk.sorai.nl) met
 * haar rollen, zodat de tegel in de launcher verschijnt en /api/access/servicedesk
 * de rollen teruggeeft. De app vertaalt de rol-slugs 1-op-1 naar haar eigen rollen.
 */
return new class extends Migration
{
    private array $rollen = [
        ['admin',       'Admin',       'Beheer van de Servicedesk-app: gebruikers, leveranciers, e-mail, KPI\'s'],
        ['servicedesk', 'Servicedesk', 'Onderdeelbestellingen afhandelen, bulkorders, orderoverzichten'],
        ['monteur',     'Monteur',     'Onderdelen aanvragen en eigen bestellingen volgen'],
    ];

    public function up(): void
    {
        $now = now();
        $appId = DB::table('applications')->where('slug', 'servicedesk')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name' => 'Servicedesk',
                'slug' => 'servicedesk',
                'description' => 'Parts System: onderdelen aanvragen (monteurs) en bestellen/afhandelen (servicedesk), met leveranciers en KPI\'s.',
                'url' => 'https://servicedesk.sorai.nl/login.php?core=1',
                'icon' => 'bi-headset',
                'color' => '#FF6600',
                'sort_order' => 74,
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
        $appId = DB::table('applications')->where('slug', 'servicedesk')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
