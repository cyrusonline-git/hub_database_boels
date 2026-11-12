<?php

namespace App\Services\Sap;

/**
 * Welk SAP-rapport is dit? Op basis van de kopregel (niet de bestandsnaam:
 * de twee 8.7-varianten heten hetzelfde). Geeft de soort-sleutel uit
 * config('sap.soorten') of null, plus een reden als het een bekend fout bestand is.
 */
class Herkenning
{
    /** @return array{soort: ?string, reden: ?string, koprij: ?int, kolommen: array} */
    public static function herken(array $rijen): array
    {
        foreach ((array) config('sap.soorten') as $soort => $cfg) {
            $kop = SapBestand::kop($rijen, $cfg['kop']);
            if ($kop) {
                return ['soort' => $soort, 'reden' => null, 'koprij' => $kop[0], 'kolommen' => $kop[1]];
            }
        }
        $eerste = [];
        foreach (array_slice($rijen, 0, 3) as $rij) {
            foreach ($rij as $v) {
                if ($v !== null && trim((string) $v) !== '') {
                    $eerste[] = trim((string) $v);
                }
            }
        }

        return ['soort' => null, 'reden' => 'Kopregel niet herkend. Gevonden: '.implode(' | ', array_slice($eerste, 0, 8)), 'koprij' => null, 'kolommen' => []];
    }
}
