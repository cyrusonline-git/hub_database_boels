<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Scanner-app live: de launcher-tegel wijst nog naar de testversie
 * (http://scanner.sorai.nl/v2) terwijl de app sinds 28-08-2026 op de root
 * draait. Tegel naar https://scanner.sorai.nl en actief.
 *
 * Rollen gelijktrekken met de app (CORE is sinds 04-09-2026 leidend voor de
 * scanner-rollen): CORE-rol 'projecten' heet in de app 'projectmanager' —
 * de rij wordt hernoemd (zelfde id, dus toegekende gebruikers blijven
 * gekoppeld); admin en medewerker worden aangevuld als ze ontbreken.
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $app = DB::table('applications')->where('slug', 'scanner')->whereNull('deleted_at')->first();
        if (! $app) {
            return;
        }

        DB::table('applications')->where('id', $app->id)->update([
            'url'        => 'https://scanner.sorai.nl',
            'active'     => true,
            'updated_at' => $now,
        ]);

        // 'projecten' → 'projectmanager' (alleen als die naam nog niet bestaat)
        $oud = DB::table('roles')->where('application_id', $app->id)->where('slug', 'projecten')->whereNull('deleted_at')->first();
        $nieuwBestaat = DB::table('roles')->where('application_id', $app->id)->where('slug', 'projectmanager')->whereNull('deleted_at')->exists();
        if ($oud && ! $nieuwBestaat) {
            DB::table('roles')->where('id', $oud->id)->update([
                'slug'        => 'projectmanager',
                'name'        => 'Projectmanager',
                'description' => 'Alles van eigen project(en): leden, in/uit, vermissingen, exports',
                'updated_at'  => $now,
            ]);
        }

        $rollen = [
            ['admin',          'Beheerder',         'Volledig beheer van de Scanner App: projecten, toegang, materieellijst'],
            ['projectmanager', 'Projectmanager',    'Alles van eigen project(en): leden, in/uit, vermissingen, exports'],
            ['medewerker',     'Projectmedewerker', 'In- en uitscannen en vermissingen zien binnen eigen project(en)'],
        ];
        foreach ($rollen as [$slug, $name, $beschrijving]) {
            $bestaat = DB::table('roles')->where('application_id', $app->id)->where('slug', $slug)->whereNull('deleted_at')->exists();
            if (! $bestaat) {
                DB::table('roles')->insert([
                    'name' => $name, 'slug' => $slug, 'description' => $beschrijving,
                    'is_system' => false, 'application_id' => $app->id,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Bewust leeg: herstelactie op data.
    }
};
