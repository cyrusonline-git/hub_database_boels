<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transportcalculator — vaste "Vrachtwagenheffing" van € 18,00 per rit voor echte
 * vrachtwagens (wagentype-id vanaf 9: kraanwagens, trekkers, side-loader), bovenop
 * de bestaande KM-heffing per postcodegebied. Bestelwagens en bakwagens met
 * laadklep (id 1–8) krijgen deze heffing niet. Wens Wim, 02-11-2026.
 *
 * Voegt alleen de sleutel `vrachtwagenheffing` toe aan de centrale tariefdataset
 * (transport_settings) als die nog ontbreekt; bestaande tarieven blijven staan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_settings')) {
            return;
        }
        $row = DB::table('transport_settings')->orderBy('id')->first();
        if (! $row) {
            return;
        }
        $p = json_decode($row->payload, true) ?: [];
        if (empty($p['jaren']) || isset($p['vrachtwagenheffing'])) {
            return;
        }
        $p['vrachtwagenheffing'] = [
            'bedrag' => 18.0,
            'vanafMiddelId' => 9,
            'omschrijving' => 'Vaste vrachtwagenheffing per rit (Boels), alleen voor echte vrachtwagens: kraanwagens, trekkers en side-loader (id vanaf 9). Komt bovenop de KM-heffing per postcodegebied.',
        ];
        $notities = array_values(array_filter($p['notities'] ?? [], fn ($n) => ! str_starts_with((string) $n, 'Vrachtwagenheffing')));
        array_splice($notities, min(2, count($notities)), 0, ['Vrachtwagenheffing: vast bedrag per rit voor vrachtwagens (id 9 t/m 16), bovenop de KM-heffing.']);
        $p['notities'] = $notities;

        DB::table('transport_settings')->where('id', $row->id)->update([
            'payload' => json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'version' => (int) $row->version + 1,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Data-migratie; bewust leeg.
    }
};
