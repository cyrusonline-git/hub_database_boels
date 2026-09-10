<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tweede eenmalige leegmaakactie van de Go-Assets aanvraagtool (Wim,
 * 10-09-2026): er stond nog een proefaanvraag die niet in testmodus was
 * gedaan. Alles weg (aanvragen, log, mails), tabellen blijven; nummering
 * begint weer bij GA-2026-001. Draait één keer.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['go_assets_mails', 'go_assets_log', 'go_assets_projecten'] as $tabel) {
            if (Schema::hasTable($tabel)) {
                DB::table($tabel)->delete();
            }
        }
        try {
            DB::statement('ALTER TABLE go_assets_mails AUTO_INCREMENT = 1');
        } catch (\Throwable $e) {
            // niet essentieel
        }
    }

    public function down(): void
    {
        // Bewust leeg.
    }
};
