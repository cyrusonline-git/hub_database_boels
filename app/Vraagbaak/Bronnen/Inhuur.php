<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Inhuur (SQLite): rentals (depot_id, supplier_id, article_name, subgroup_number, quantity, hire_rate_week = inkoop,
 * rental_rate_week = verhuur, start_date, expected_return_date, actual_return_date, status, archived, created_at),
 * suppliers (name), depots (name, area_id).
 */
class Inhuur
{
    private const BRON = 'inhuur';
    private const FROM = 'FROM rentals r JOIN depots d ON r.depot_id = d.id LEFT JOIN suppliers s ON r.supplier_id = s.id';

    private static function depotW(array $p, array &$bind): string
    {
        return empty($p['depot']) ? '1=1' : Depots::likeClause($p['depot'], 'd.name', $bind);
    }

    public static function vragen(): array
    {
        $f = self::FROM;

        return [
            new Vraag('inhuur.lopend', self::BRON, 'Wat loopt er nu in huur (per vestiging), en wat kost dat per week?',
                [['inhuur', 'ingehuurd', 'in huur', 'huren we', 'inhuren', 'gehuurd', 'lopende inhuur', 'lopend']],
                ['nu', 'lopend', 'staat', 'per week', 'kost'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, COUNT(*) AS regels, SUM(r.quantity) AS stuks, ROUND(SUM(COALESCE(r.hire_rate_week,0) * COALESCE(r.quantity,1)),2) AS inkoop_pw, ROUND(SUM(COALESCE(r.rental_rate_week,0) * COALESCE(r.quantity,1)),2) AS verhuur_pw $f WHERE r.status = 'IN HUUR' AND $w GROUP BY d.name ORDER BY inkoop_pw DESC", $bind);
                    $tot = ['Regels' => array_sum(array_column($rijen, 'regels')), 'Stuks' => array_sum(array_column($rijen, 'stuks')), 'Inkoop per week' => '€ '.number_format(array_sum(array_column($rijen, 'inkoop_pw')), 2, ',', '.')];

                    return Resultaat::tabel(['Vestiging', 'Regels', 'Stuks', 'Inkoop/week', 'Verhuur/week'], array_map(fn ($x) => [$x['vestiging'], (int) $x['regels'], (int) $x['stuks'], (float) $x['inkoop_pw'], (float) $x['verhuur_pw']], $rijen), 'Regels met status IN HUUR in de Inhuur-app'.(! empty($p['depot']) ? ', vestiging '.$p['depot']['naam'] : '').'. Inkoop = wat de leverancier ons rekent, verhuur = wat wij de klant rekenen.', $tot);
                }),

            new Vraag('inhuur.nieuw', self::BRON, 'Hoeveel nieuwe inhuur is er gestart (per vestiging, periode)?',
                [['inhuur', 'ingehuurd', 'inhuren', 'gehuurd'], ['nieuw', 'nieuwe', 'gestart', 'begonnen', 'aantal', 'hoeveel']],
                ['deze maand', 'dit jaar'], ['depot', 'periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [$p['periode']['van_s'], $p['periode']['tm_s']];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, COUNT(*) AS regels, SUM(r.quantity) AS stuks, COUNT(DISTINCT r.supplier_id) AS leveranciers $f WHERE r.start_date BETWEEN ? AND ? AND $w GROUP BY d.name ORDER BY regels DESC", $bind);

                    return Resultaat::tabel(['Vestiging', 'Nieuwe regels', 'Stuks', 'Leveranciers'], array_map(fn ($x) => [$x['vestiging'], (int) $x['regels'], (int) $x['stuks'], (int) $x['leveranciers']], $rijen), 'Inhuurregels met startdatum in '.$p['periode']['label'].'.', ['Totaal regels' => array_sum(array_column($rijen, 'regels'))]);
                }),

            new Vraag('inhuur.leveranciers', self::BRON, 'Welke leveranciers kosten het meest (top 10)?',
                [['leverancier', 'leveranciers', 'verhuurder', 'verhuurders']],
                ['inhuur', 'top', 'meeste', 'kosten', 'duurste'], ['depot', 'periode', 'top'], 'manager', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $n = (int) ($p['top'] ?: 10);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(s.name,'onbekend') AS leverancier, COUNT(*) AS regels, ROUND(SUM(COALESCE(r.hire_rate_week,0)*COALESCE(r.quantity,1)),2) AS kosten_pw $f WHERE r.status = 'IN HUUR' AND $w GROUP BY s.name ORDER BY kosten_pw DESC LIMIT $n", $bind);

                    return Resultaat::tabel(['Leverancier', 'Lopende regels', 'Kosten per week'], array_map(fn ($x) => [$x['leverancier'], (int) $x['regels'], (float) $x['kosten_pw']], $rijen), "Top $n leveranciers op weekkosten van lopende inhuur (status IN HUUR).");
                }),

            new Vraag('inhuur.te_laat', self::BRON, 'Welke inhuur is over de verwachte retourdatum (te laat)?',
                [['te laat', 'over datum', 'verlopen', 'retourdatum', 'over de datum', 'overdue', 'te lang']],
                ['inhuur', 'retour'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, COALESCE(s.name,'-') AS leverancier, r.article_name, r.expected_return_date, CAST(julianday('now') - julianday(r.expected_return_date) AS INT) AS dagen, ROUND(COALESCE(r.hire_rate_week,0)/7.0*(julianday('now')-julianday(r.expected_return_date))*COALESCE(r.quantity,1),2) AS extra $f WHERE r.status IN ('IN HUUR','AFGEMELD') AND r.expected_return_date < date('now') AND $w ORDER BY dagen DESC LIMIT 100", $bind);

                    return Resultaat::tabel(['Vestiging', 'Leverancier', 'Artikel', 'Verwacht retour', 'Dagen te laat', 'Extra kosten (schatting)'], array_map(fn ($x) => [$x['vestiging'], $x['leverancier'], $x['article_name'], $x['expected_return_date'], (int) $x['dagen'], (float) $x['extra']], $rijen), 'Regels IN HUUR/AFGEMELD waarvan de verwachte retourdatum voorbij is; extra kosten = weektarief ÷ 7 × dagen te laat.', ['Regels' => count($rijen), 'Extra kosten' => '€ '.number_format(array_sum(array_column($rijen, 'extra')), 2, ',', '.')]);
                }),

            new Vraag('inhuur.marge', self::BRON, 'Wat is de marge op lopende inhuur per vestiging?',
                [['marge', 'marges', 'winst', 'verdienen we', 'opbrengst']],
                ['inhuur', 'vestiging'], ['depot'], 'manager', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, ROUND(SUM((COALESCE(r.rental_rate_week,0)-COALESCE(r.hire_rate_week,0))*COALESCE(r.quantity,1)),2) AS marge_pw, ROUND(100.0*SUM((COALESCE(r.rental_rate_week,0)-COALESCE(r.hire_rate_week,0))*COALESCE(r.quantity,1))/NULLIF(SUM(COALESCE(r.rental_rate_week,0)*COALESCE(r.quantity,1)),0),1) AS pct $f WHERE r.status='IN HUUR' AND $w GROUP BY d.name ORDER BY marge_pw", $bind);

                    return Resultaat::tabel(['Vestiging', 'Marge per week', 'Marge %'], array_map(fn ($x) => [$x['vestiging'], (float) $x['marge_pw'], $x['pct'] === null ? '-' : $x['pct'].' %'], $rijen), 'Verhuurtarief min inkooptarief per week over lopende inhuur.');
                }),

            new Vraag('inhuur.duur', self::BRON, 'Wat is de gemiddelde huurduur van afgeronde inhuur?',
                [['gemiddelde', 'gemiddeld', 'huurduur', 'hoe lang', 'duur']],
                ['inhuur', 'retour', 'dagen'], ['depot', 'periode'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [$p['periode']['van_s'], $p['periode']['tm_s']];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, COUNT(*) AS afgerond, ROUND(AVG(julianday(r.actual_return_date)-julianday(r.start_date)),1) AS gem_dagen $f WHERE r.actual_return_date IS NOT NULL AND r.actual_return_date BETWEEN ? AND ? AND $w GROUP BY d.name ORDER BY gem_dagen DESC", $bind);

                    return Resultaat::tabel(['Vestiging', 'Afgerond', 'Gemiddelde huurduur (dagen)'], array_map(fn ($x) => [$x['vestiging'], (int) $x['afgerond'], (float) $x['gem_dagen']], $rijen), 'Regels met werkelijke retourdatum in '.$p['periode']['label'].'.');
                }),

            new Vraag('inhuur.subgroepen', self::BRON, 'Welk materieel huren we het vaakst in (subgroepen)?',
                [['subgroep', 'subgroepen', 'vaakst', 'meest ingehuurd', 'welk materieel', 'wat huren we', 'artikel', 'artikelen', 'eigen vloot']],
                ['inhuur', 'inhuren', 'top'], ['depot', 'periode', 'top'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [$p['periode']['van_s'], $p['periode']['tm_s']];
                    $w = self::depotW($p, $bind);
                    $n = (int) ($p['top'] ?: 15);
                    $rijen = $b->select(self::BRON, "SELECT r.subgroup_number AS subgroep, MAX(r.article_name) AS omschrijving, COUNT(*) AS keer, SUM(r.quantity) AS stuks $f WHERE r.start_date BETWEEN ? AND ? AND $w GROUP BY r.subgroup_number ORDER BY keer DESC LIMIT $n", $bind);

                    return Resultaat::tabel(['Subgroep', 'Omschrijving', 'Keer ingehuurd', 'Stuks'], array_map(fn ($x) => [$x['subgroep'], $x['omschrijving'], (int) $x['keer'], (int) $x['stuks']], $rijen), "Top $n subgroepen op aantal inhuurregels in {$p['periode']['label']} (signaal voor eigen vlootaankoop).");
                }),

            new Vraag('inhuur.concept', self::BRON, 'Welke inhuur staat nog op concept (niet verstuurd)?',
                [['concept', 'concepten', 'niet verstuurd', 'nog niet verzonden', 'open aanvragen']],
                ['inhuur'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, COUNT(*) AS concepten, MIN(substr(r.created_at,1,10)) AS oudste $f WHERE r.status = 'CONCEPT' AND COALESCE(r.archived,0) = 0 AND $w GROUP BY d.name ORDER BY concepten DESC", $bind);

                    return Resultaat::tabel(['Vestiging', 'Concepten', 'Oudste'], array_map(fn ($x) => [$x['vestiging'], (int) $x['concepten'], $x['oudste']], $rijen), 'Inhuurregels met status CONCEPT die nog niet naar de leverancier zijn verstuurd.');
                }),

            new Vraag('inhuur.leverancier_detail', self::BRON, 'Wat huren we in bij leverancier "X"?',
                [['bij leverancier', 'van leverancier', 'leverancier']],
                ['inhuur', 'huren'], ['depot', 'naam'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    if (empty($p['naam'])) {
                        return Resultaat::tekst('Noem de leverancier tussen aanhalingstekens, bijvoorbeeld: wat huren we in bij leverancier "Riwal"?');
                    }
                    $bind = ['%'.$p['naam'].'%'];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT d.name AS vestiging, r.article_name, r.quantity, r.start_date, r.expected_return_date, r.status, COALESCE(r.hire_rate_week,0) AS inkoop $f WHERE s.name LIKE ? AND r.status IN ('IN HUUR','OPDRACHT VERZONDEN','AFGEMELD') AND $w ORDER BY r.start_date DESC LIMIT 100", $bind);

                    return Resultaat::tabel(['Vestiging', 'Artikel', 'Aantal', 'Start', 'Verwacht retour', 'Status', 'Inkoop/week'], array_map(fn ($x) => [$x['vestiging'], $x['article_name'], (int) $x['quantity'], $x['start_date'], $x['expected_return_date'], $x['status'], (float) $x['inkoop']], $rijen), 'Lopende inhuurregels bij leverancier "'.$p['naam'].'".');
                }),
        ];
    }
}
