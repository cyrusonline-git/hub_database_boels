<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Transporttarieven — centrale bron + rekenlogica.
 *
 * PHP-port van de calculator-logica (bereken/vindBand) uit
 * resources/views/tools/transport.blade.php, zodat de tool-UI én de interne
 * tarief-API (voor de transportplanner) met exact dezelfde formule rekenen.
 * De dataset staat in de tabel `transport_settings` (één JSON-blob-row).
 *
 * Formule: basis = band[1+middelId];
 *          naToeslag = basis * (1 + periode.toeslag);
 *          inkoop (KOSTEN)   = naToeslag * (1 + dot);
 *          verkoop (OMZET)   = inkoop * (1 + marge).
 * De dieseltoeslag (dot) is koerier of vracht, afhankelijk van dotDrempelId,
 * en kan per maand uit dotMaanden worden genomen (reproduceerbaar per rit-datum).
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

    /** Postcodeband zoeken: laatste band waarvan PC_VAN <= postcode (Excel-benadering, zoals de tool). */
    public static function vindBand(array $payload, string $jaar, int $postcode): ?array
    {
        $rows = $payload['jaren'][$jaar]['rows'] ?? [];
        $gevonden = null;
        foreach ($rows as $row) {
            if ((int) $row[0] <= $postcode) {
                $gevonden = $row;
            } else {
                break;
            }
        }
        return $gevonden;
    }

    /**
     * Bereken kosten (inkoop) + verkoop (omzet) voor een postcode + wagentype + periode.
     *
     * @param  int|string  $postcode  4-cijferige postcode (cijferdeel)
     * @param  int  $middelId  wagentype-id (1..16)
     * @param  int  $periodeIdx  0=werkdag, 1=avond/nacht, 2=zaterdag, 3=zondag/feestdag
     * @param  string|null  $jaar  tarievenjaar; null = actiefJaar uit de dataset
     * @param  string|null  $maand  'YYYY-MM' voor dot uit dotMaanden; null = top-level dotKoerier/dotVracht
     * @return array  ok + bedragen, of ok=false + fout
     */
    public static function bereken($postcode, int $middelId, int $periodeIdx = 0, ?string $jaar = null, ?string $maand = null): array
    {
        $p = self::payload();
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
        $band = self::vindBand($p, $jaar, $pc);
        if (! $band) {
            return ['ok' => false, 'error' => "Postcode $pc ligt onder de laagste postcodeband."];
        }
        $basis = $band[1 + $middelId] ?? null;
        if ($basis === null || $basis === '' || ! is_numeric($basis)) {
            return ['ok' => false, 'error' => "Voor dit wagentype staat geen tarief in de tabel ($jaar, band {$band[0]}–{$band[1]})."];
        }
        $basis = (float) $basis;

        $periodes = $p['periodes'] ?? [];
        $per = $periodes[$periodeIdx] ?? ($periodes[0] ?? ['naam' => 'Werkdag', 'toeslag' => 0]);
        $toeslag = (float) ($per['toeslag'] ?? 0);

        // Dieseltoeslag: koerier of vracht, evt. uit de maand-tabel.
        $drempel = (int) ($p['dotDrempelId'] ?? 8);
        $isKoerier = $middelId <= $drempel;
        $dot = $isKoerier ? (float) ($p['dotKoerier'] ?? 0) : (float) ($p['dotVracht'] ?? 0);
        if ($maand) {
            foreach ($p['dotMaanden'] ?? [] as $dm) {
                if (($dm['maand'] ?? '') === $maand) {
                    $dot = $isKoerier ? (float) ($dm['koerier'] ?? $dot) : (float) ($dm['vracht'] ?? $dot);
                    break;
                }
            }
        }
        $marge = (float) ($p['marge'] ?? 0);

        $naToeslag = $basis * (1 + $toeslag);
        $inkoop = $naToeslag * (1 + $dot);
        $verkoop = $inkoop * (1 + $marge);

        return [
            'ok' => true,
            'jaar' => $jaar,
            'postcode' => $pc,
            'band' => [$band[0], $band[1]],
            'buitenBand' => $pc > (int) $band[1],
            'middel_id' => $middelId,
            'periode' => $per['naam'] ?? '',
            'periode_idx' => $periodeIdx,
            'basis' => round($basis, 2),
            'toeslag_pct' => $toeslag,
            'dot_pct' => $dot,
            'dot_soort' => $isKoerier ? 'koerier' : 'vracht',
            'marge_pct' => $marge,
            'inkoop' => round($inkoop, 2),   // KOSTEN
            'verkoop' => round($verkoop, 2), // OMZET / klant-tarief
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
        ];
    }
}
