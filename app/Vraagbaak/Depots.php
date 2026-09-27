<?php

namespace App\Vraagbaak;

use App\Models\OrgDepot;
use Illuminate\Support\Facades\Cache;

/**
 * Depot of area uit een vraag halen en vertalen naar zoektermen per app.
 * Basis = CORE-infrastructuur (org_depots: naam, plaats, nummers; org_areas) + vaste aliassen.
 * Resultaat: ['type' => 'depot'|'area', 'naam' => 'Rotterdam-Europoort', 'termen' => ['Rotterdam','Europoort'], 'nummers' => ['759'], 'match' => 'rdam']
 */
class Depots
{
    /** Alle depots uit CORE met zoektermen. */
    public static function lijst(): array
    {
        return Cache::remember('vraagbaak:depots', 300, function () {
            $uit = [];
            foreach (OrgDepot::with('area')->get() as $d) {
                $termen = self::termenUitNaam($d->name);
                if ($d->city) {
                    $termen = array_merge($termen, self::termenUitNaam($d->city));
                }
                $uit[] = ['naam' => $d->name, 'plaats' => $d->city, 'area' => $d->area?->name, 'nummers' => method_exists($d, 'numbers') ? $d->numbers() : [], 'termen' => array_values(array_unique($termen))];
            }

            return $uit;
        });
    }

    /** "Rotterdam-Europoort" -> ['rotterdam', 'europoort']; "Geleen - Chemelot" -> ['geleen','chemelot'] */
    public static function termenUitNaam(string $naam): array
    {
        $n = mb_strtolower($naam);
        $delen = preg_split('/[\s\-\/,()]+/u', $n);

        return array_values(array_filter(array_map('trim', $delen), fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, ['industrial', 'boels', 'depot', 'vestiging', 'area', 'regio', 'zuid', 'noord', 'west', 'oost'], true)));
    }

    /** Zoek depot of area in de vraagtekst. */
    public static function herken(string $t): ?array
    {
        $t = ' '.mb_strtolower($t).' ';
        // 1. vaste aliassen (rdam, chemelot, ...)
        foreach ((array) config('vraagbaak.depot_aliassen') as $alias => $kern) {
            if (preg_match('/[\s\'"(]'.preg_quote($alias, '/').'[\s\'".,?!)]/u', $t)) {
                $depot = self::depotMetTerm(mb_strtolower($kern));

                return ['type' => 'depot', 'naam' => $depot['naam'] ?? $kern, 'termen' => array_values(array_unique(array_merge([$kern], $depot['termen'] ?? []))), 'nummers' => $depot['nummers'] ?? [], 'match' => $alias];
            }
        }
        // 2. termen uit de CORE-depotnamen/plaatsen
        foreach (self::lijst() as $d) {
            foreach ($d['termen'] as $term) {
                if (preg_match('/[\s\'"(]'.preg_quote($term, '/').'[\s\'".,?!)]/u', $t)) {
                    return ['type' => 'depot', 'naam' => $d['naam'], 'termen' => array_values(array_unique(array_merge([$term], $d['termen']))), 'nummers' => $d['nummers'], 'match' => $term];
                }
            }
        }
        // 3. depotnummer (3-4 cijfers) — alleen als er "depot" of "vestiging" bij staat
        if (preg_match('/\b(?:depot|vestiging)\s*(\d{3,4})\b/', $t, $m)) {
            foreach (self::lijst() as $d) {
                if (in_array($m[1], $d['nummers'], true)) {
                    return ['type' => 'depot', 'naam' => $d['naam'], 'termen' => $d['termen'], 'nummers' => $d['nummers'], 'match' => $m[0]];
                }
            }
        }
        // 4. areas
        foreach ((array) config('vraagbaak.area_aliassen') as $alias => $area) {
            if (preg_match('/[\s\'"(](?:area\s+|regio\s+)?'.preg_quote($alias, '/').'[\s\'".,?!)]/u', $t)) {
                $depots = array_values(array_filter(self::lijst(), fn ($d) => mb_strtolower((string) $d['area']) === mb_strtolower($area)));
                $termen = [];
                $nummers = [];
                foreach ($depots as $d) {
                    $termen = array_merge($termen, $d['termen']);
                    $nummers = array_merge($nummers, $d['nummers']);
                }

                return ['type' => 'area', 'naam' => $area, 'termen' => array_values(array_unique($termen)), 'nummers' => array_values(array_unique($nummers)), 'depots' => array_column($depots, 'naam'), 'match' => $alias];
            }
        }

        return null;
    }

    private static function depotMetTerm(string $term): ?array
    {
        foreach (self::lijst() as $d) {
            if (in_array($term, $d['termen'], true) || str_contains(mb_strtolower($d['naam']), $term)) {
                return $d;
            }
        }

        return null;
    }

    /**
     * SQL-fragment "(kolom LIKE ? OR kolom LIKE ?)" voor een depot/area-keuze, met bindings.
     * Voor apps met een naamkolom; areas geven alle depots van die area.
     */
    public static function likeClause(?array $depot, string $kolom, array &$bind): string
    {
        if (! $depot || empty($depot['termen'])) {
            return '1=1';
        }
        $delen = [];
        foreach ($depot['termen'] as $term) {
            $delen[] = "$kolom LIKE ?";
            $bind[] = '%'.$term.'%';
        }

        return '('.implode(' OR ', $delen).')';
    }
}
