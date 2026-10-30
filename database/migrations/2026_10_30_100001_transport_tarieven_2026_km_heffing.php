<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transportcalculator — nieuwe Excel "Boels Industrial Abird prijzen incl KM-heffing 2026" (09-10-2026).
 *
 * Werkt de centrale tariefdataset (transport_settings, één JSON-row) bij met alles uit de
 * vier tabbladen van de nieuwe calculator:
 *  - Tarieven De Jong Europoort: tariefjaar 2026 (zelfde kale tarieven als 2025), banden met
 *    plaats / 2-cijferig postcodegebied / km, nieuwe middel-namen (kraan 40 en 60 ton/meter) en per
 *    middel het uurtarief + laad-/lostijd.
 *  - Brandstoftoeslag 2026: DOT 27,5% trucks / 11,5% busjes (jan–okt), grens busjes = id t/m 6.
 *  - KM-heffing: vrachtwagenheffing per rit heen en weer per postcodegebied (alleen id 9 t/m 16),
 *    met vaste bedragen voor het havengebied (postcodegebied 31).
 *  - Calculator Europoort: voetnoten (Z-Air/Hacon, tol/pont/veer/weeg).
 *
 * Door planners handmatig aangepaste tarieven van eerdere jaren blijven staan; alleen de hier
 * genoemde sleutels worden overschreven/toegevoegd. Bron: database/data/transport_default.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_settings')) {
            return;
        }
        $pad = database_path('data/transport_default.json');
        $nieuw = is_file($pad) ? json_decode(file_get_contents($pad), true) : null;
        if (! is_array($nieuw) || empty($nieuw['jaren']['2026'])) {
            return;
        }

        $row = DB::table('transport_settings')->orderBy('id')->first();
        if (! $row) {
            DB::table('transport_settings')->insert([
                'id' => 1,
                'payload' => json_encode($nieuw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'version' => 1, 'updated_by' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return;
        }

        $p = json_decode($row->payload, true) ?: [];
        if (empty($p['jaren'])) {
            $p = $nieuw;
        } else {
            // Tariefjaar 2026 toevoegen (bestaand 2026 — bv. zelf geïndexeerd — niet overschrijven)
            if (empty($p['jaren']['2026'])) {
                $p['jaren']['2026'] = $nieuw['jaren']['2026'];
            }
            $p['actiefJaar'] = '2026';
            $p['banden'] = $nieuw['banden'];
            $p['middelen'] = $nieuw['middelen'];
            $p['periodes'] = $nieuw['periodes'];
            $p['dotDrempelId'] = $nieuw['dotDrempelId'];
            $p['dotKoerier'] = $nieuw['dotKoerier'];
            $p['dotVracht'] = $nieuw['dotVracht'];
            // DOT-maanden: 2026 vervangen door de nieuwe Brandstoftoeslag-tabel, oudere jaren behouden
            $oud = array_values(array_filter($p['dotMaanden'] ?? [], fn ($m) => ! str_starts_with((string) ($m['maand'] ?? ''), '2026-')));
            $p['dotMaanden'] = array_merge($oud, array_values(array_filter($nieuw['dotMaanden'], fn ($m) => str_starts_with((string) ($m['maand'] ?? ''), '2026-'))));
            $p['kmHeffing'] = $nieuw['kmHeffing'];
            $p['notities'] = $nieuw['notities'];
            $p['vestiging'] = $p['vestiging'] ?? $nieuw['vestiging'];
            $p['bron'] = $nieuw['bron'];
        }

        DB::table('transport_settings')->where('id', $row->id)->update([
            'payload' => json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'version' => (int) $row->version + 1,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Data-migratie; de vorige dataset is niet 1-op-1 te herstellen. Bewust leeg.
    }
};
