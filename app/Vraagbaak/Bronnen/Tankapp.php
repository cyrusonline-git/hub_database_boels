<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Tankapp (SQLite): aftankingen (depot_id, liters, fuel_type, status, machine_number, contract_number,
 * has_damage, archived, andere_vestiging_id, liters_ander_depot, created_at), depots (name, area_id), areas (name).
 * 'BLS' is een label, geen vestiging: altijd uitsluiten.
 */
class Tankapp
{
    private const BRON = 'tankapp';

    /** WHERE-deel voor periode + depot/area + brandstof. */
    private static function filter(array $p, array &$bind, string $alias = 'a', string $depotAlias = 'd'): string
    {
        $w = ["$alias.created_at >= ?", "$alias.created_at <= ?", "$depotAlias.name != 'BLS'"];
        $bind[] = $p['periode']['van_s'].' 00:00:00';
        $bind[] = $p['periode']['tm_s'].' 23:59:59';
        if (! empty($p['depot'])) {
            if ($p['depot']['type'] === 'area') {
                $w[] = '(ar.name LIKE ? OR '.Depots::likeClause($p['depot'], "$depotAlias.name", $bind).')';
                array_splice($bind, count($bind) - count($p['depot']['termen']), 0, ['%'.$p['depot']['naam'].'%']);
            } else {
                $w[] = Depots::likeClause($p['depot'], "$depotAlias.name", $bind);
            }
        }
        if (! empty($p['brandstof'])) {
            $w[] = "$alias.fuel_type = ?";
            $bind[] = $p['brandstof'];
        }

        return implode(' AND ', $w);
    }

    private static function uitleg(array $p, string $wat): string
    {
        $d = $p['depot']['naam'] ?? 'alle vestigingen';
        $b = $p['brandstof'] ?? 'alle brandstoffen';

        return "$wat in de Tankapp, periode {$p['periode']['label']}, vestiging: $d, brandstof: $b (BLS-label uitgesloten).";
    }

    public static function vragen(): array
    {
        $from = 'FROM aftankingen a JOIN depots d ON a.depot_id = d.id LEFT JOIN areas ar ON d.area_id = ar.id';

        return [
            new Vraag('tank.liters', self::BRON, 'Hoeveel liter is er getankt (per vestiging, periode)?',
                [['liter', 'liters', 'getankt', 'afgetankt', 'tanken', 'tankbeurt', 'brandstof', 'diesel', 'adblue']],
                ['hoeveel', 'totaal', 'tank'], ['depot', 'periode', 'brandstof'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $r = $b->select(self::BRON, "SELECT COALESCE(SUM(a.liters),0) AS liters, COUNT(*) AS aantal, COUNT(DISTINCT a.machine_number) AS machines $from WHERE $w", $bind)[0];
                    $bind2 = [];
                    $w2 = self::filter($p, $bind2);
                    $per = $b->select(self::BRON, "SELECT d.name AS vestiging, ROUND(SUM(a.liters),1) AS liters, COUNT(*) AS tankbeurten $from WHERE $w2 GROUP BY d.name ORDER BY liters DESC", $bind2);
                    $res = Resultaat::getal(round((float) $r['liters'], 1), 'liter', self::uitleg($p, 'Som van alle liters'), ['Tankbeurten' => (int) $r['aantal'], 'Machines' => (int) $r['machines']]);
                    if (count($per) > 1) {
                        $res->kolommen = ['Vestiging', 'Liters', 'Tankbeurten'];
                        $res->rijen = array_map(fn ($x) => [$x['vestiging'], (float) $x['liters'], (int) $x['tankbeurten']], $per);
                    }

                    return $res;
                }, 'Som van aftankingen.liters'),

            new Vraag('tank.top_depots', self::BRON, 'Welke vestigingen tanken het meest (top 10)?',
                [['vestiging', 'vestigingen', 'depot', 'depots', 'top', 'meeste', 'ranglijst'], ['liter', 'liters', 'getankt', 'tanken', 'tank', 'brandstof']],
                ['meest', 'grootste', 'hoogste'], ['periode', 'brandstof', 'top'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $n = (int) ($p['top'] ?: 10);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, ROUND(SUM(a.liters),1) AS liters, COUNT(*) AS tankbeurten $from WHERE $w GROUP BY d.name ORDER BY liters DESC LIMIT $n", $bind);

                    return Resultaat::tabel(['Vestiging', 'Liters', 'Tankbeurten'], array_map(fn ($x) => [$x['vestiging'], (float) $x['liters'], (int) $x['tankbeurten']], $rijen), self::uitleg($p, "Top $n vestigingen op liters"));
                }),

            new Vraag('tank.top_machines', self::BRON, 'Welke machines verbruiken het meest (top 10)?',
                [['machine', 'machines', 'machinenummer', 'materieel'], ['liter', 'liters', 'getankt', 'tanken', 'verbruik', 'verbruiken', 'brandstof']],
                ['meest', 'top'], ['depot', 'periode', 'brandstof', 'top'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $n = (int) ($p['top'] ?: 10);
                    $rijen = $b->select(self::BRON, "SELECT a.machine_number AS machine, COUNT(*) AS keer, ROUND(SUM(a.liters),1) AS liters, MAX(d.name) AS vestiging $from WHERE $w AND a.machine_number IS NOT NULL AND a.machine_number != '' GROUP BY a.machine_number ORDER BY liters DESC LIMIT $n", $bind);

                    return Resultaat::tabel(['Machine', 'Liters', 'Tankbeurten', 'Vestiging (laatste)'], array_map(fn ($x) => [$x['machine'], (float) $x['liters'], (int) $x['keer'], $x['vestiging']], $rijen), self::uitleg($p, "Top $n machines op liters"));
                }),

            new Vraag('tank.machine', self::BRON, 'Tankbeurten van machine 1234567',
                [['machine', 'machinenummer', 'getankt', 'tankbeurt', 'tankbeurten', 'liter', 'liters']],
                [], ['periode', 'machine'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($from) {
                    if (empty($p['machine'])) {
                        return Resultaat::tekst('Noem het machinenummer, bijvoorbeeld: "tankbeurten van machine 7590019510 dit jaar".');
                    }
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $bind[] = '%'.$p['machine'].'%';
                    $rijen = $b->select(self::BRON, "SELECT substr(a.created_at,1,10) AS datum, d.name AS vestiging, a.liters, a.fuel_type, a.status, a.contract_number $from WHERE $w AND a.machine_number LIKE ? ORDER BY a.created_at DESC LIMIT 100", $bind);
                    $tot = array_sum(array_column($rijen, 'liters'));

                    return Resultaat::tabel(['Datum', 'Vestiging', 'Liters', 'Brandstof', 'Status', 'Contract'], array_map(fn ($x) => [$x['datum'], $x['vestiging'], (float) $x['liters'], $x['fuel_type'], $x['status'], $x['contract_number']], $rijen), self::uitleg($p, 'Tankbeurten van machine '.$p['machine']), ['Totaal liters' => round($tot, 1), 'Tankbeurten' => count($rijen)]);
                }),

            new Vraag('tank.status', self::BRON, 'Hoeveel is doorbelast, niet doorbelast en eigen gebruik?',
                [['doorbelast', 'niet doorbelast', 'eigen gebruik', 'doorbelasting', 'verdeling', 'status']],
                ['liter', 'liters', 'tank', 'getankt'], ['depot', 'periode'], 'manager', 'jaar',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT a.status, COUNT(*) AS aantal, ROUND(SUM(a.liters),1) AS liters $from WHERE $w GROUP BY a.status ORDER BY liters DESC", $bind);
                    $tot = max(1, array_sum(array_column($rijen, 'liters')));

                    return Resultaat::tabel(['Status', 'Tankbeurten', 'Liters', 'Aandeel'], array_map(fn ($x) => [$x['status'] ?: 'onbekend', (int) $x['aantal'], (float) $x['liters'], round(100 * $x['liters'] / $tot, 1).' %'], $rijen), self::uitleg($p, 'Liters per status (doorbelast / niet doorbelast / eigen gebruik / in behandeling)'));
                }),

            new Vraag('tank.brandstof', self::BRON, 'Liters per brandstofsoort (diesel, AdBlue) per area',
                [['brandstofsoort', 'brandstofsoorten', 'per brandstof', 'soort brandstof', 'diesel en adblue', 'adblue en diesel', 'brandstoffen']],
                ['area', 'liter', 'liters'], ['depot', 'periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(ar.name,'-') AS area, a.fuel_type AS brandstof, ROUND(SUM(a.liters),1) AS liters, COUNT(*) AS n $from WHERE $w GROUP BY ar.name, a.fuel_type ORDER BY ar.name, liters DESC", $bind);

                    return Resultaat::tabel(['Area', 'Brandstof', 'Liters', 'Tankbeurten'], array_map(fn ($x) => [$x['area'], $x['brandstof'], (float) $x['liters'], (int) $x['n']], $rijen), self::uitleg($p, 'Liters per area en brandstofsoort'));
                }),

            new Vraag('tank.cross', self::BRON, 'Wie tankt voor welke andere vestiging (cross-depot)?',
                [['andere vestiging', 'ander depot', 'cross', 'voor elkaar', 'voor een andere', 'tankt voor', 'getankt voor']],
                ['liter', 'liters', 'vestiging'], ['depot', 'periode'], 'manager', 'maand',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = self::filter($p, $bind, 'a', 'df');
                    $rijen = $b->select(self::BRON, "SELECT df.name AS van, dt.name AS voor, ROUND(SUM(a.liters),1) AS liters, COUNT(*) AS n, ROUND(SUM(COALESCE(a.liters_ander_depot,0)),1) AS doorbelastbaar FROM aftankingen a JOIN depots df ON a.depot_id = df.id JOIN depots dt ON a.andere_vestiging_id = dt.id LEFT JOIN areas ar ON df.area_id = ar.id WHERE $w GROUP BY df.name, dt.name ORDER BY liters DESC", $bind);

                    return Resultaat::tabel(['Getankt door', 'Voor vestiging', 'Liters', 'Tankbeurten', 'Doorbelastbaar (l)'], array_map(fn ($x) => [$x['van'], $x['voor'], (float) $x['liters'], (int) $x['n'], (float) $x['doorbelastbaar']], $rijen), self::uitleg($p, 'Tankbeurten met een andere vestiging als afnemer'));
                }),

            new Vraag('tank.verlies', self::BRON, 'Hoeveel liter is niet meer doorbelastbaar (verlies)?',
                [['verlies', 'verloren', 'niet doorbelastbaar', 'niet meer doorbelastbaar', 'kwijt', 'weggegooid']],
                ['liter', 'liters', 'tank'], ['depot', 'periode'], 'manager', 'maand',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, ROUND(SUM(a.liters - COALESCE(a.liters_ander_depot, a.liters)),1) AS verloren, COUNT(*) AS n $from WHERE $w AND a.liters_ander_depot IS NOT NULL AND a.liters_ander_depot < a.liters GROUP BY d.name ORDER BY verloren DESC", $bind);
                    $tot = array_sum(array_column($rijen, 'verloren'));

                    return Resultaat::tabel(['Vestiging', 'Niet doorbelastbaar (l)', 'Tankbeurten'], array_map(fn ($x) => [$x['vestiging'], (float) $x['verloren'], (int) $x['n']], $rijen), self::uitleg($p, 'Liters min het doorbelastbare deel (liters_ander_depot)'), ['Totaal' => round($tot, 1).' l']);
                }),

            new Vraag('tank.werkvoorraad', self::BRON, 'Wat staat er nog open voor de binnendienst (niet gearchiveerd)?',
                [['open', 'openstaand', 'werkvoorraad', 'nog verwerken', 'te verwerken', 'niet gearchiveerd', 'binnendienst', 'zonder contract']],
                ['tank', 'tankbeurten', 'tankapp'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($from) {
                    $bind = [];
                    $w = "d.name != 'BLS' AND COALESCE(a.archived,0) = 0";
                    if (! empty($p['depot'])) {
                        $w .= ' AND '.Depots::likeClause($p['depot'], 'd.name', $bind);
                    }
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, COUNT(*) AS te_verwerken, SUM(CASE WHEN a.contract_number IS NULL OR a.contract_number = '' THEN 1 ELSE 0 END) AS zonder_contract, SUM(COALESCE(a.has_damage,0)) AS met_schade, MIN(substr(a.created_at,1,10)) AS oudste $from WHERE $w GROUP BY d.name ORDER BY te_verwerken DESC", $bind);

                    return Resultaat::tabel(['Vestiging', 'Te verwerken', 'Zonder contract', 'Met schade', 'Oudste'], array_map(fn ($x) => [$x['vestiging'], (int) $x['te_verwerken'], (int) $x['zonder_contract'], (int) $x['met_schade'], $x['oudste']], $rijen), 'Tankbeurten die nog niet gearchiveerd zijn in de Tankapp (werkvoorraad binnendienst).', ['Totaal' => array_sum(array_column($rijen, 'te_verwerken'))]);
                }),

            new Vraag('tank.trend', self::BRON, 'Trend: liters per maand (laatste 12 maanden)',
                [['trend', 'per maand', 'verloop', 'ontwikkeling', 'maandelijks', 'grafiek']],
                ['liter', 'liters', 'tank', 'getankt'], ['depot', 'brandstof'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($from) {
                    $p['periode'] = ['van_s' => now()->subMonths(12)->startOfMonth()->format('Y-m-d'), 'tm_s' => now()->format('Y-m-d'), 'label' => 'laatste 12 maanden'];
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT substr(a.created_at,1,7) AS maand, ROUND(SUM(a.liters),1) AS liters, COUNT(*) AS n $from WHERE $w GROUP BY maand ORDER BY maand", $bind);

                    return Resultaat::tabel(['Maand', 'Liters', 'Tankbeurten'], array_map(fn ($x) => [$x['maand'], (float) $x['liters'], (int) $x['n']], $rijen), self::uitleg($p, 'Liters per maand'));
                }),
        ];
    }
}
