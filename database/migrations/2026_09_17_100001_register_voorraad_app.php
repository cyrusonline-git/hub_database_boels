<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registreert de Voorraad tool (voorraad.sorai.nl, Laravel child-app) met
 * vijf app-rollen. De app spiegelt de rollen via /api/access/voorraad.
 */
return new class extends Migration
{
    private array $rollen = [
        'binnendienst' => ['Binnendienst', 'Contract/project uploaden en zien op welk depot materieel beschikbaar is; aanvraagmail naar depot'],
        'werkplaats'   => ['Werkplaats', 'Ziet per depot wat nagekeken moet worden om de minimale voorraad te halen'],
        'manager'      => ['Manager', 'Ziet per depot of de minimale voorraad gehaald wordt en wat op service/reparatie staat'],
        'fleet'        => ['Fleet', 'Totaaloverzicht: welk materieel staat waar, met welke status'],
        'admin'        => ['Beheerder', 'Instellingen, depotkoppeling en mailtemplates van de Voorraad tool'],
    ];

    public function up(): void
    {
        $now = now();
        $appId = DB::table('applications')->where('slug', 'voorraad')->value('id');
        if (! $appId) {
            $appId = DB::table('applications')->insertGetId([
                'name'        => 'Voorraad tool',
                'slug'        => 'voorraad',
                'description' => 'Materieel zoeken per depot voor contracten/projecten en minimale voorraad bewaken',
                'url'         => 'https://voorraad.sorai.nl',
                'icon'        => 'bi-boxes',
                'color'       => '#FF6600',
                'sort_order'  => 70,
                'active'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        foreach ($this->rollen as $slug => [$naam, $omschrijving]) {
            $rolId = DB::table('roles')->where('application_id', $appId)
                ->where('slug', $slug)->whereNull('deleted_at')->value('id');
            if (! $rolId) {
                $rolId = DB::table('roles')->insertGetId([
                    'name'           => $naam,
                    'slug'           => $slug,
                    'description'    => $omschrijving,
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
        $appId = DB::table('applications')->where('slug', 'voorraad')->value('id');
        if ($appId) {
            DB::table('application_role')->where('application_id', $appId)->delete();
            DB::table('roles')->where('application_id', $appId)->delete();
            DB::table('applications')->where('id', $appId)->delete();
        }
    }
};
