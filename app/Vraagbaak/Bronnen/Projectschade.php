<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Projectschade (SQLite): damage_headers (depot_id, project_id, contractnummer, klantnaam, created_at) +
 * damage_items (bd_status, totaal_schadebedrag, doorbelast_bedrag, arbeidskosten, onderdelenkosten, inspectiekosten,
 * schade_type, is_inhuur, na_deadline), depots (naam, area_id), areas (naam), projects (projectnaam, klantnaam).
 * Doorbelast = bd_status 'Afgehandeld' → COALESCE(doorbelast_bedrag, totaal_schadebedrag).
 */
class Projectschade
{
    private const BRON = 'projectschade';
    private const FROM = 'FROM damage_items di JOIN damage_headers dh ON di.damage_header_id = dh.id LEFT JOIN depots d ON dh.depot_id = d.id LEFT JOIN areas a ON d.area_id = a.id';
    private const DOORBELAST = "SUM(CASE WHEN di.bd_status='Afgehandeld' THEN COALESCE(di.doorbelast_bedrag, di.totaal_schadebedrag, 0) ELSE 0 END)";

    private static function filter(array $p, array &$bind): string
    {
        $w = ['dh.created_at >= ?', 'dh.created_at <= ?'];
        $bind[] = $p['periode']['van_s'].' 00:00:00';
        $bind[] = $p['periode']['tm_s'].' 23:59:59';
        if (! empty($p['depot'])) {
            $w[] = $p['depot']['type'] === 'area' ? '(a.naam LIKE ? OR '.Depots::likeClause($p['depot'], 'd.naam', $bind).')' : Depots::likeClause($p['depot'], 'd.naam', $bind);
            if ($p['depot']['type'] === 'area') {
                array_splice($bind, count($bind) - count($p['depot']['termen']), 0, ['%'.$p['depot']['naam'].'%']);
            }
        }

        return implode(' AND ', $w);
    }

    private static function uitleg(array $p, string $wat): string
    {
        return "$wat in Projectschade, periode {$p['periode']['label']}".(! empty($p['depot']) ? ', vestiging '.$p['depot']['naam'] : '').'. Doorbelast = afgehandelde schades (doorbelast bedrag, anders vastgesteld bedrag).';
    }

    public static function vragen(): array
    {
        $f = self::FROM;
        $db = self::DOORBELAST;

        return [
            new Vraag('schade.aantal', self::BRON, 'Hoeveel schades zijn er gemeld en voor hoeveel euro doorbelast?',
                [['schade', 'schades', 'schademelding', 'schademeldingen', 'projectschade', 'doorbelast', 'schadebedrag']],
                ['hoeveel', 'aantal', 'euro', 'bedrag'], ['depot', 'periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) use ($f, $db) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $r = $b->select(self::BRON, "SELECT COUNT(*) AS n, $db AS doorbelast, SUM(COALESCE(di.totaal_schadebedrag,0)) AS vastgesteld, SUM(CASE WHEN di.bd_status='Afgehandeld' THEN 1 ELSE 0 END) AS afgehandeld $f WHERE $w", $bind)[0];
                    $bind2 = [];
                    $w2 = self::filter($p, $bind2);
                    $per = $b->select(self::BRON, "SELECT COALESCE(d.naam,'-') AS vestiging, COUNT(*) AS n, ROUND($db,2) AS doorbelast $f WHERE $w2 GROUP BY d.naam ORDER BY doorbelast DESC", $bind2);
                    $res = Resultaat::getal((int) $r['n'], 'schades', self::uitleg($p, 'Aantal schaderegels'), ['Doorbelast' => '€ '.number_format((float) $r['doorbelast'], 2, ',', '.'), 'Afgehandeld' => (int) $r['afgehandeld'], 'Vastgesteld totaal' => '€ '.number_format((float) $r['vastgesteld'], 2, ',', '.')]);
                    if (count($per) > 1) {
                        $res->kolommen = ['Vestiging', 'Schades', 'Doorbelast (€)'];
                        $res->rijen = array_map(fn ($x) => [$x['vestiging'], (int) $x['n'], (float) $x['doorbelast']], $per);
                    }

                    return $res;
                }),

            new Vraag('schade.trend', self::BRON, 'Trend: schades en doorbelast bedrag per maand (12 maanden)',
                [['schade', 'schades', 'schadebedrag'], ['trend', 'per maand', 'verloop', 'ontwikkeling', 'maandelijks']],
                [], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f, $db) {
                    $p['periode'] = ['van_s' => now()->subMonths(12)->startOfMonth()->format('Y-m-d'), 'tm_s' => now()->format('Y-m-d'), 'label' => 'laatste 12 maanden'];
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT substr(dh.created_at,1,7) AS maand, COUNT(*) AS n, ROUND($db,2) AS doorbelast $f WHERE $w GROUP BY maand ORDER BY maand", $bind);

                    return Resultaat::tabel(['Maand', 'Schades', 'Doorbelast (€)'], array_map(fn ($x) => [$x['maand'], (int) $x['n'], (float) $x['doorbelast']], $rijen), self::uitleg($p, 'Schades per maand'));
                }),

            new Vraag('schade.verlies', self::BRON, 'Hoeveel schade laten we liggen (coulance en intern afgesloten)?',
                [['coulance', 'laten liggen', 'liggen', 'intern afgesloten', 'niet doorbelast', 'kwijtgescholden', 'geen schade', 'verlies']],
                ['schade', 'schades'], ['depot', 'periode'], 'manager', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT di.bd_status AS status, COUNT(*) AS n, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS bedrag $f WHERE $w AND di.bd_status IN ('Geen schade','Intern afgesloten') GROUP BY di.bd_status", $bind);

                    return Resultaat::tabel(['Status', 'Schades', 'Vastgesteld bedrag (€)'], array_map(fn ($x) => [$x['status'], (int) $x['n'], (float) $x['bedrag']], $rijen), self::uitleg($p, 'Schades die niet zijn doorbelast (coulance = Geen schade, intern afgesloten)'), ['Totaal' => '€ '.number_format(array_sum(array_column($rijen, 'bedrag')), 2, ',', '.')]);
                }),

            new Vraag('schade.open', self::BRON, 'Welke schades staan nog open (pijplijn per vestiging)?',
                [['schade', 'schades'], ['open', 'openstaand', 'openstaande', 'pijplijn', 'nog niet afgehandeld', 'lopende', 'deadline']],
                [], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = '1=1';
                    if (! empty($p['depot'])) {
                        $w = Depots::likeClause($p['depot'], 'd.naam', $bind);
                    }
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(d.naam,'-') AS vestiging, COUNT(*) AS open, ROUND(SUM(COALESCE(di.totaal_schadebedrag, di.voorgesteld_bedrag, 0)),2) AS verwacht, SUM(CASE WHEN di.na_deadline = 1 THEN 1 ELSE 0 END) AS over_deadline, MIN(substr(dh.created_at,1,10)) AS oudste $f WHERE COALESCE(di.bd_status,'') NOT IN ('Afgehandeld','Geen schade','Intern afgesloten') AND $w GROUP BY d.naam ORDER BY verwacht DESC", $bind);

                    return Resultaat::tabel(['Vestiging', 'Open', 'Verwacht bedrag (€)', 'Over deadline', 'Oudste'], array_map(fn ($x) => [$x['vestiging'], (int) $x['open'], (float) $x['verwacht'], (int) $x['over_deadline'], $x['oudste']], $rijen), 'Schaderegels die nog niet afgehandeld, coulance of intern afgesloten zijn.', ['Totaal open' => array_sum(array_column($rijen, 'open'))]);
                }),

            new Vraag('schade.types', self::BRON, 'Welke schadetypes komen het vaakst voor en wat kosten ze?',
                [['schadetype', 'schadetypes', 'soort schade', 'soorten schade', 'type schade', 'welk soort', 'vaakst', 'meest voorkomende']],
                ['schade', 'schades', 'top'], ['depot', 'periode', 'top'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $n = (int) ($p['top'] ?: 15);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(di.schade_type,'onbekend') AS type, COUNT(*) AS n, ROUND(AVG(COALESCE(di.totaal_schadebedrag,0)),2) AS gem, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS totaal $f WHERE $w GROUP BY di.schade_type ORDER BY totaal DESC LIMIT $n", $bind);

                    return Resultaat::tabel(['Schadetype', 'Aantal', 'Gemiddeld (€)', 'Totaal (€)'], array_map(fn ($x) => [$x['type'], (int) $x['n'], (float) $x['gem'], (float) $x['totaal']], $rijen), self::uitleg($p, "Top $n schadetypes op totaalbedrag"));
                }),

            new Vraag('schade.kosten', self::BRON, 'Kostenopbouw: arbeid, onderdelen, inspectie',
                [['kostenopbouw', 'arbeid', 'arbeidskosten', 'onderdelen', 'onderdelenkosten', 'inspectie', 'inspectiekosten', 'opbouw']],
                ['schade', 'schades'], ['depot', 'periode'], 'manager', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $r = $b->select(self::BRON, "SELECT ROUND(SUM(COALESCE(di.arbeidskosten,0)),2) AS arbeid, ROUND(SUM(COALESCE(di.onderdelenkosten,0)),2) AS onderdelen, ROUND(SUM(COALESCE(di.inspectiekosten,0)),2) AS inspectie, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS totaal, COUNT(*) AS n $f WHERE $w AND di.bd_status = 'Afgehandeld'", $bind)[0];

                    return Resultaat::tabel(['Onderdeel', 'Bedrag (€)'], [['Arbeid', (float) $r['arbeid']], ['Onderdelen', (float) $r['onderdelen']], ['Inspectie', (float) $r['inspectie']], ['Totaal vastgesteld', (float) $r['totaal']]], self::uitleg($p, 'Kostenopbouw van afgehandelde schades'), ['Schades' => (int) $r['n']]);
                }),

            new Vraag('schade.project', self::BRON, 'Projectschade versus losse contractschade',
                [['project', 'projecten', 'projectschade', 'los contract', 'losse schade']],
                ['schade', 'schades', 'versus', 'vs'], ['depot', 'periode'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT CASE WHEN dh.project_id IS NOT NULL THEN 'Project' ELSE 'Los contract' END AS soort, COUNT(*) AS n, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS bedrag $f WHERE $w GROUP BY soort", $bind);

                    return Resultaat::tabel(['Soort', 'Schades', 'Vastgesteld (€)'], array_map(fn ($x) => [$x['soort'], (int) $x['n'], (float) $x['bedrag']], $rijen), self::uitleg($p, 'Schades met en zonder project'));
                }),

            new Vraag('schade.klant', self::BRON, 'Schades van klant of project "X"',
                [['klant', 'klantnaam', 'opdrachtgever', 'van project', 'bij project']],
                ['schade', 'schades'], ['periode', 'naam'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    if (empty($p['naam'])) {
                        return Resultaat::tekst('Noem de klant of het project tussen aanhalingstekens, bijvoorbeeld: schades van klant "Shell" dit jaar.');
                    }
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $bind[] = '%'.$p['naam'].'%';
                    $bind[] = '%'.$p['naam'].'%';
                    $rijen = $b->select(self::BRON, "SELECT substr(dh.created_at,1,10) AS datum, COALESCE(d.naam,'-') AS vestiging, dh.klantnaam, dh.contractnummer, di.schade_type, di.bd_status, ROUND(COALESCE(di.totaal_schadebedrag,0),2) AS bedrag $f LEFT JOIN projects pr ON dh.project_id = pr.id WHERE $w AND (dh.klantnaam LIKE ? OR pr.projectnaam LIKE ?) ORDER BY dh.created_at DESC LIMIT 100", $bind);

                    return Resultaat::tabel(['Datum', 'Vestiging', 'Klant', 'Contract', 'Type', 'Status', 'Bedrag (€)'], array_map(fn ($x) => [$x['datum'], $x['vestiging'], $x['klantnaam'], $x['contractnummer'], $x['schade_type'], $x['bd_status'], (float) $x['bedrag']], $rijen), 'Schades waarvan klantnaam of projectnaam "'.$p['naam'].'" bevat, '.$p['periode']['label'].'.', ['Totaal' => '€ '.number_format(array_sum(array_column($rijen, 'bedrag')), 2, ',', '.')]);
                }),

            new Vraag('schade.inhuur', self::BRON, 'Hoeveel schade is er aan ingehuurd materieel?',
                [['inhuurschade', 'ingehuurd materieel', 'schade inhuur', 'schade aan inhuur', 'inhuur schade']],
                ['schade', 'schades', 'leverancier'], ['depot', 'periode'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::filter($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(di.inhuur_leverancier,'onbekend') AS leverancier, COUNT(*) AS n, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS bedrag, ROUND(SUM(COALESCE(di.inhuur_leverancier_bedrag,0)),2) AS leverancier_bedrag $f WHERE $w AND (di.is_inhuur = 1 OR di.status = 'Schade Inhuur') GROUP BY di.inhuur_leverancier ORDER BY bedrag DESC", $bind);

                    return Resultaat::tabel(['Leverancier', 'Schades', 'Vastgesteld (€)', 'Leveranciersbedrag (€)'], array_map(fn ($x) => [$x['leverancier'], (int) $x['n'], (float) $x['bedrag'], (float) $x['leverancier_bedrag']], $rijen), self::uitleg($p, 'Schades gemarkeerd als inhuur'));
                }),
        ];
    }
}
