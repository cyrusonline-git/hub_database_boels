<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Registreert de Projecten-app (projecten.sorai.nl, slug projecten) zoals "Koppel app via URL"
 * (ApplicationController@registerFromUrl) dat doet: app + launcher-tegel (Boels-oranje) + de 7 rollen uit
 * https://projecten.sorai.nl/core-roles.php (slugs via Str::slug, dus bu_manager → bu-manager, zoals bij
 * de koppeling; apps vertalen streepjes zelf naar underscores). Geen core-users.php → geen gebruikersimport.
 */
return new class extends Migration
{
    private array $rollen = [
        'werkvoorbereider' => ['Werkvoorbereider', 'Stelt de materiaallijst op en bestelt meerwerk.'],
        'uitvoerder'       => ['Uitvoerder', 'Voorman op de plant; vult uren van de ploeg in.'],
        'projectleider'    => ['Projectleider', 'Leidt het project en maakt de calculatie.'],
        'projectmanager'   => ['Projectmanager', 'Keurt uren en estimates goed, voert de close-out.'],
        'administratie'    => ['Administratie', 'Projectnummers, contracten, uploads en facturatie.'],
        'bu_manager'       => ['BU manager', 'Overzicht over alle projecten en de close-out.'],
        'admin'            => ['Beheerder', 'Volledige toegang inclusief instellingen.'],
    ];

    public function up(): void
    {
        $now = now();
        $app = DB::table('applications')->where('slug', 'projecten')->first();
        if (! $app) {
            $appId = DB::table('applications')->insertGetId([
                'name' => 'Projecten', 'slug' => 'projecten', 'description' => 'Projecten: materiaallijsten, uren, estimates en close-out',
                'url' => 'https://projecten.sorai.nl', 'icon' => 'bi-kanban', 'color' => '#FF6600', 'sort_order' => 75,
                'active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        } else {
            $appId = $app->id;
            DB::table('applications')->where('id', $appId)->update(['url' => 'https://projecten.sorai.nl', 'active' => true, 'deleted_at' => null,
                'color' => $app->color ?: '#FF6600', 'updated_at' => $now]);
        }

        foreach ($this->rollen as $bron => [$naam, $omschrijving]) {
            $slug = Str::slug($bron);
            $rol = DB::table('roles')->where('application_id', $appId)->where('slug', $slug)->first();
            if (! $rol) {
                $rolId = DB::table('roles')->insertGetId([
                    'name' => $naam, 'slug' => $slug, 'description' => $omschrijving, 'is_system' => false,
                    'application_id' => $appId, 'created_at' => $now, 'updated_at' => $now,
                ]);
            } else {
                $rolId = $rol->id;
                if (! empty($rol->deleted_at)) {
                    DB::table('roles')->where('id', $rolId)->update(['deleted_at' => null, 'updated_at' => $now]);
                }
            }
            if (! DB::table('application_role')->where('application_id', $appId)->where('role_id', $rolId)->exists()) {
                DB::table('application_role')->insert(['application_id' => $appId, 'role_id' => $rolId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Bewust niets verwijderen: rollen kunnen al aan gebruikers hangen.
    }
};
