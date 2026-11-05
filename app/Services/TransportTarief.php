<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Transporttarieven — centrale bron + rekenlogica.
 *
 * PHP-port van de calculator-logica uit resources/views/tools/transport.blade.php,
 * zodat de tool-UI én de interne tarief-API (voor de transportplanner) met exact
 * dezelfde formule rekenen. De dataset staat in de tabel `transport_settings`
 * (één JSON-blob-row).
 *
 * Formule (Excel "Boels Industrial Abird prijzen incl KM-heffing 2026", tab Calculator Europoort):
 *   basis        = tarief[postcodeband][middel]                 (tab Tarieven De Jong Europoort)
 *   exclDot      = basis * (1 + periode.toeslag)                 (W17:Y20 %-toeslag)
 *   inclDot      = exclDot * (1 + dot)                           (tab Brandstoftoeslag: id > dotDrempelId = trucks)
 *   kmHeffing    = alleen middel-id >= kmHeffing.vanafMiddelId:  (tab KM-heffing, heen en weer per 2-cijferig
 *                  bedrag per postcodegebied van de band,         postcodegebied; gebied 31 = vast bedrag per band)
 *                  of de uitzondering voor de band
 *   inkoop       = inclDot + kmHeffing                           ("ALL-IN" in Excel)  -> KOSTEN
 *   vrachtwagenheffing = vast bedrag per rit (Boels, € 18) voor middel-id >= vrachtwagenheffing.vanafMiddelId;
 *                  staat LOS van inkoop/verkoop (geen marge) en wordt apart onder het tarief getoond
 *   verkoop      = inkoop * (1 + marge)                          (eigen Boels-marge, niet in Excel) -> OMZET
 *   uurtarief    = middel.uurtarief * (1 + toeslag) [* (1 + dot)] (wachturen / Z-Air / Hacon)
 */
class TransportTarief
{
    /** De (enige) settings-row; maakt 'm aan met een leeg object als hij ontbreekt.
     *  Valt veilig terug op een leeg object als de tabel nog niet bestaat (migratie niet gedraaid). */
    public static function settingsRow(): object
    {
        try {
            $row = DB::table('transport_settings')->orderBy('id')->first();
        } catch (\Throwable $e) {
            return (object) ['id' => 0, 'payload' => '{}', 'version' => 1];
        }
        if (! $row) {
            DB::table('transport_settings')->insert([
                'id' => 1, 'payload' => '{}', 'version' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = DB::table('transport_settings')->orderBy('id')->first();
        }
        return $row;
    }

    /** De tariefdataset als array. */
    public static function payload(): array
    {
        return json_decode(self::settingsRow()->payload, true) ?: [];
    }

    /** Index van de postcodeband: laatste band waarvan PC_VAN <= postcode (Excel VLOOKUP-benadering). */
    public static function vindBandIndex(array $payload, string $jaar, int $postcode): ?int
    {
        $rows = $payload['jaren'][$jaar]['rows'] ?? [];
        $gevonden = null;
        foreach ($rows as $i => $row) {
            if ((int) $row[0] <= $postcode) {
                $gevonden = $i;
            } else {
                break;
            }
        }
        return $gevonden;
    }

    /** Postcodeband zoeken (de rij zelf). */
    public static function vindBand(array $payload, string $jaar, int $postcode): ?array
    {
        $i = self::vindBandIndex($payload, $jaar, $postcode);
        return $i === null ? null : $payload['jaren'][$jaar]['rows'][$i];
    }

    /** Dieseltoeslag voor een middel, evt. uit de maand-tabel (null in de maand = terugvallen op actueel). */
    public static function dot(array $p, int $middelId, ?string $maand = null): array
    {
        $drempel = (int) ($p['dotDrempelId'] ?? 6);
        $isKoerier = $middelId <= $drempel;
        $dot = $isKoerier ? (float) ($p['dotKoerier'] ?? 0) : (float) ($p['dotVracht'] ?? 0);
        $bron = 'actueel';
        if ($maand) {
            foreach ($p['dotMaanden'] ?? [] as $dm) {
                if (($dm['maand'] ?? '') === $maand) {
                    $v = $isKoerier ? ($dm['koerier'] ?? null) : ($dm['vracht'] ?? null);
                    if ($v !== null && $v !== '') {
                        $dot = (float) $v;
                        $bron = $maand;
                    }
                    break;
                }
            }
        }
        return ['dot' => $dot, 'isKoerier' => $isKoerier, 'bron' => $bron];
    }

    /**
     * KM-heffing (vrachtwagenheffing) voor een band + middel: bedrag heen en weer, of 0.
     * Volgt tab KM-heffing: alleen middel-id >= vanafMiddelId; postcodegebied 31 (haven) heeft
     * een vast bedrag per band (uitzonderingen), anders het bedrag van het 2-cijferige gebied.
     */
    public static function kmHeffing(array $p, int $middelId, int $bandIndex): array
    {
        $kh = $p['kmHeffing'] ?? null;
        $band = $p['banden'][$bandIndex] ?? null;
        $uit = ['bedrag' => 0.0, 'geldt' => false, 'gebied' => $band['gebied'] ?? null, 'plaats' => $band['plaats'] ?? null, 'bron' => null];
        if (! $kh || ! $band) {
            return $uit;
        }
        if ($middelId < (int) ($kh['vanafMiddelId'] ?? 9)) {
            return $uit;
        }
        $uit['geldt'] = true;
        foreach ($kh['uitzonderingen'] ?? [] as $u) {
            if ((int) $u['van'] === (int) $band['van'] && (int) $u['tot'] === (int) $band['tot']) {
                $uit['bedrag'] = (float) $u['bedrag'];
                $uit['bron'] = 'uitzondering';
                return $uit;
            }
        }
        $g = $kh['perGebied'][(string) $band['gebied']] ?? null;
        if ($g !== null) {
            $uit['bedrag'] = (float) ($g['retour'] ?? 0);
            $uit['bron'] = 'gebied';
        }
        return $uit;
    }

    /** Vaste vrachtwagenheffing per rit (alleen echte vrachtwagens: id vanaf vanafMiddelId). */
    public static function vrachtwagenheffing(array $p, int $middelId): array
    {
        $v = $p['vrachtwagenheffing'] ?? null;
        $bedrag = (float) ($v['bedrag'] ?? 0);
        $vanaf = (int) ($v['vanafMiddelId'] ?? 9);
        $geldt = $v !== null && $bedrag > 0 && $middelId >= $vanaf;
        return ['bedrag' => $geldt ? $bedrag : 0.0, 'geldt' => $geldt, 'vanafMiddelId' => $vanaf];
    }

    /**
     * Bereken kosten (inkoop) + verkoop (omzet) voor een postcode + wagentype + periode.
     *
     * @param  int|string  $postcode  4-cijferige postcode (cijferdeel)
     * @param  int  $middelId  wagentype-id (1..16)
     * @param  int  $periodeIdx  0=werkdag, 1=avond/nacht, 2=zaterdag, 3=zondag/feestdag
     * @param  string|null  $jaar  tarievenjaar; null = actiefJaar uit de dataset
     * @param  string|null  $maand  'YYYY-MM' voor dot uit dotMaanden; null = actuele dotKoerier/dotVracht
     * @return array  ok + bedragen, of ok=false + fout
     */
    public static function bereken($postcode, int $middelId, int $periodeIdx = 0, ?string $jaar = null, ?string $maand = null): array
    {
        return self::berekenMetPayload(self::payload(), $postcode, $middelId, $periodeIdx, $jaar, $maand);
    }

    /** Zelfde berekening, maar met een meegegeven dataset (testbaar zonder database). */
    public static function berekenMetPayload(array $p, $postcode, int $middelId, int $periodeIdx = 0, ?string $jaar = null, ?string $maand = null): array
    {
        if (empty($p['jaren'])) {
            return ['ok' => false, 'error' => 'Geen tarieven ingesteld.'];
        }
        $jaar = $jaar ?: ($p['actiefJaar'] ?? array_key_first($p['jaren']));
        if (! isset($p['jaren'][$jaar])) {
            return ['ok' => false, 'error' => "Geen tarieven voor jaar $jaar."];
        }
        $pc = (int) preg_replace('/\D/', '', (string) $postcode);
        if ($pc < 1000) {
            return ['ok' => false, 'error' => 'Ongeldige postcode (4 cijfers nodig).'];
        }
        $bandIndex = self::vindBandIndex($p, $jaar, $pc);
        if ($bandIndex === null) {
            return ['ok' => false, 'error' => "Postcode $pc ligt onder de laagste postcodeband."];
        }
        $band = $p['jaren'][$jaar]['rows'][$bandIndex];
        $basis = $band[1 + $middelId] ?? null;
        if ($basis === null || $basis === '' || ! is_numeric($basis)) {
            return ['ok' => false, 'error' => "Voor dit wagentype staat geen tarief in de tabel ($jaar, band {$band[0]}–{$band[1]})."];
        }
        $basis = (float) $basis;

        $periodes = $p['periodes'] ?? [];
        $per = $periodes[$periodeIdx] ?? ($periodes[0] ?? ['naam' => 'Werkdag', 'toeslag' => 0]);
        $toeslag = (float) ($per['toeslag'] ?? 0);

        $d = self::dot($p, $middelId, $maand);
        $dot = $d['dot'];
        $marge = (float) ($p['marge'] ?? 0);

        $exclDot = $basis * (1 + $toeslag);
        $inclDot = $exclDot * (1 + $dot);
        $km = self::kmHeffing($p, $middelId, $bandIndex);
        $vwh = self::vrachtwagenheffing($p, $middelId);
        $inkoop = $inclDot + $km['bedrag'];          // ALL-IN (Excel E12); vrachtwagenheffing staat hier los van
        $verkoop = $inkoop * (1 + $marge);

        // Uurtarief + tijden van het middel (tab Tarieven, autotypen-tabel)
        $middel = null;
        foreach ($p['middelen'] ?? [] as $m) {
            if ((int) ($m['id'] ?? 0) === $middelId) { $middel = $m; break; }
        }
        $uur = isset($middel['uurtarief']) && is_numeric($middel['uurtarief']) ? (float) $middel['uurtarief'] * (1 + $toeslag) : null;
        $meta = $p['banden'][$bandIndex] ?? null;

        return [
            'ok' => true,
            'jaar' => $jaar,
            'postcode' => $pc,
            'band' => [$band[0], $band[1]],
            'band_index' => $bandIndex,
            'plaats' => $meta['plaats'] ?? null,
            'pc_gebied' => $meta['gebied'] ?? null,
            'km' => $meta['km'] ?? null,
            'buitenBand' => $pc > (int) $band[1],
            'middel_id' => $middelId,
            'middel_naam' => $middel['naam'] ?? null,
            'periode' => $per['naam'] ?? '',
            'periode_idx' => $periodeIdx,
            'basis' => round($basis, 2),
            'toeslag_pct' => $toeslag,
            'dot_pct' => $dot,
            'dot_soort' => $d['isKoerier'] ? 'koerier' : 'vracht',
            'dot_bron' => $d['bron'],
            'marge_pct' => $marge,
            'tarief_excl_dot' => round($exclDot, 2),
            'tarief_incl_dot' => round($inclDot, 2),
            'km_heffing' => round($km['bedrag'], 2),
            'km_heffing_geldt' => $km['geldt'],
            'vrachtwagenheffing' => round($vwh['bedrag'], 2),   // los van inkoop/verkoop, zonder marge
            'vrachtwagenheffing_geldt' => $vwh['geldt'],
            'totaal_incl_heffing' => round($verkoop + $vwh['bedrag'], 2), // transporttarief + vrachtwagenheffing
            'inkoop' => round($inkoop, 2),   // KOSTEN (all-in: incl. DOT + KM-heffing)
            'verkoop' => round($verkoop, 2), // OMZET / klant-tarief
            'uurtarief' => $uur === null ? null : round($uur, 2),
            'uurtarief_incl_dot' => $uur === null ? null : round($uur * (1 + $dot), 2),
            'wachtkosten_kwartier' => $uur === null ? null : round($uur / 4, 2),
            'laadlos_uur' => isset($middel['laadLosUur']) && is_numeric($middel['laadLosUur']) ? (float) $middel['laadLosUur'] : null,
            'laadlos_incl_min' => isset($middel['laadLosInclMin']) && is_numeric($middel['laadLosInclMin']) ? (int) $middel['laadLosInclMin'] : null,
        ];
    }

    /** Wagentypes (middelen) + periodes, voor de planner-dropdowns. */
    public static function middelen(): array
    {
        $p = self::payload();
        return [
            'middelen' => $p['middelen'] ?? [],
            'periodes' => array_map(fn ($per, $i) => ['idx' => $i, 'naam' => $per['naam'] ?? ''], $p['periodes'] ?? [], array_keys($p['periodes'] ?? [])),
            'actiefJaar' => $p['actiefJaar'] ?? null,
            'jaren' => array_keys($p['jaren'] ?? []),
            'kmHeffingVanafId' => (int) ($p['kmHeffing']['vanafMiddelId'] ?? 9),
            'vrachtwagenheffing' => (float) ($p['vrachtwagenheffing']['bedrag'] ?? 0),
            'vrachtwagenheffingVanafId' => (int) ($p['vrachtwagenheffing']['vanafMiddelId'] ?? 9),
            'dotDrempelId' => (int) ($p['dotDrempelId'] ?? 6),
        ];
    }
}
