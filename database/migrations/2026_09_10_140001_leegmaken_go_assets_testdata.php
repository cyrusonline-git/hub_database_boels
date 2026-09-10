<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eenmalig leegmaken van de Go-Assets aanvraagtool (op verzoek van Wim,
 * 10-09-2026): alles tot nu toe was testdata. Verwijdert alle aanvragen,
 * logregels en bewaarde mails; de tabellen zelf blijven bestaan en de
 * nummering begint weer bij GA-2026-001. Draait één keer (migrations-tabel).
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
        // Auto-increment van de mails-tabel terug naar 1 (de andere hebben tekst-id's)
        try {
            DB::statement('ALTER TABLE go_assets_mails AUTO_INCREMENT = 1');
        } catch (\Throwable $e) {
            // niet essentieel
        }
    }

    public function down(): void
    {
        // Bewust leeg: verwijderde testdata komt niet terug.
    }
};
