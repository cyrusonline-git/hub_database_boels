<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Herstel: de Projectschade-app vroeg /api/access/projectschade op en kreeg
 * 404 "Onbekende applicatie" — er is geen actieve applicatie meer met slug
 * `projectschade` (vermoedelijk is het slug-veld in Beheer → Applicaties
 * leeggemaakt, waarna hij automatisch uit de naam is afgeleid).
 *
 * De app-code gebruikt de vaste slug `projectschade`, dus die zetten we
 * terug op de actieve applicatie die naar projectschade.sorai.nl wijst.
 * Rollen van een eventuele oude (zacht verwijderde) rij met die slug gaan
 * mee naar de actieve rij, en de rol bu-manager wordt aangevuld (die
 * migratie vond de app eerder niet). Idempotent en alleen-bij-nood: als er
 * al een actieve app met slug `projectschade` is, gebeurt er niets.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $actief = DB::table('applications')->where('slug', 'projectschade')->whereNull('deleted_at')->first();

        if (! $actief) {
            $kandidaat = DB::table('applications')->whereNull('deleted_at')
                ->where(fn ($q) => $q->where('url', 'like', '%projectschade.sorai.nl%')
                    ->orWhere('name', 'like', '%projectschade%'))
                ->orderBy('id')->first();

            if (! $kandidaat) {
                // Alleen een zacht verwijderde rij? Dan die herstellen.
                $trashed = DB::table('applications')->where('slug', 'projectschade')->whereNotNull('deleted_at')->orderBy('id')->first();
                if ($trashed) {
                    DB::table('applications')->where('id', $trashed->id)->update(['deleted_at' => null, 'updated_at' => $now]);
                    Log::info("repair_projectschade_slug: zacht verwijderde app #{$trashed->id} hersteld");
                    $actief = DB::table('applications')->where('id', $trashed->id)->first();
                } else {
                    Log::warning('repair_projectschade_slug: geen Projectschade-app gevonden — niets gedaan');
                    return;
                }
            } else {
                // Bezetters van de slug (kunnen alleen zacht verwijderde rijen zijn) hernoemen
                $bezetters = DB::table('applications')->where('slug', 'projectschade')->where('id', '!=', $kandidaat->id)->get();
                foreach ($bezetters as $b) {
                    DB::table('applications')->where('id', $b->id)->update(['slug' => 'projectschade-oud-' . $b->id, 'updated_at' => $now]);
                    // Rollen die de actieve rij nog niet heeft, verhuizen mee
                    $heeft = DB::table('roles')->where('application_id', $kandidaat->id)->pluck('slug')->all();
                    DB::table('roles')->where('application_id', $b->id)->whereNotIn('slug', $heeft)
                        ->update(['application_id' => $kandidaat->id, 'updated_at' => $now]);
                    Log::info("repair_projectschade_slug: oude rij #{$b->id} hernoemd, rollen verhuisd naar #{$kandidaat->id}");
                }

                DB::table('applications')->where('id', $kandidaat->id)
                    ->update(['slug' => 'projectschade', 'updated_at' => $now]);
                Log::info("repair_projectschade_slug: slug van app #{$kandidaat->id} ('{$kandidaat->slug}') teruggezet op 'projectschade'");
                $actief = DB::table('applications')->where('id', $kandidaat->id)->first();
            }
        }

        // Rol bu-manager aanvullen (de migratie van vanochtend vond de app niet)
        $rollen = [
            ['superadmin',   'Superadmin (Boels)', 'Volledig beheer: app-instellingen en toegangsbeheer'],
            ['admin',        'Beheerder',          'Beperkt beheer: alleen lokale accounts'],
            ['expeditie',    'Expeditie',          'Schades melden en retourafhandeling'],
            ['servicedesk',  'Servicedesk',        'Schadebeoordeling en onderdelenbeheer'],
            ['binnendienst', 'Binnendienst',       'Doorbelasting en klantcommunicatie'],
            ['werkplaats',   'Werkplaats',         'Reparaties en werkplaatsafhandeling'],
            ['area-manager', 'Area Manager',       'Overzichten van alle depots in de eigen area'],
            ['bu-manager',   'BU Manager',         'Landelijk overzicht: alle areas en alle depots (read-only)'],
        ];
        foreach ($rollen as [$slug, $name, $beschrijving]) {
            $bestaat = DB::table('roles')->where('application_id', $actief->id)->where('slug', $slug)->whereNull('deleted_at')->exists();
            if (! $bestaat) {
                DB::table('roles')->insert([
                    'name' => $name, 'slug' => $slug, 'description' => $beschrijving,
                    'is_system' => false, 'application_id' => $actief->id,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                Log::info("repair_projectschade_slug: rol '$slug' toegevoegd aan app #{$actief->id}");
            }
        }
    }

    public function down(): void
    {
        // Bewust leeg: dit is een herstelactie op data.
    }
};
