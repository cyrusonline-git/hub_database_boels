<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Voorraad tool (SQLite): materieel (upload_id, uniek_nr, subgroep_nr, subgroep_naam, depot_nummer, depot_naam, status_code, laatste_uithuur),
 * uploads (type, actueel), depots (naam, depot_nummer, nummer_core, area), reserveringen (depot_nummer, subgroep_nr, startdatum, aantal),
 * aanvragen (depot_naam, aantal_machines, status, created_at), min_voorraad (depot_nummer, subgroep_nr, minimum).
 * Altijd filteren op de actuele upload.
 */
class Voorraad
{
    private const BRON = 'voorraad';
    private const ACTUEEL = "m.upload_id IN (SELECT id FROM uploads WHERE type='materieel' AND actueel=1)";
    private const STATUS = ['available' => 'Beschikbaar', 'in_service' => 'In service', 'in_repair' => 'In reparatie', 'in_transfer' => 'Onderweg', 'on_hire' => 'Verhuurd', 'own_use' => 'Eigen gebruik', 'composed' => 'Samengesteld', 'onbekend' => 'Onbekend'];

    private static function depotW(array $p, array &$bind, string $kol = 'd.naam'): string
    {
        if (empty($p['depot'])) {
            return '1=1';
        }
        $c = Depots::likeClause($p['depot'], $kol, $bind);
        foreach ($p['depot']['nummers'] ?? [] as $nr) {
            $c = '('.$c.' OR m.depot_nummer = ?)';
            $bind[] = $nr;
        }

        return $c;
    }

    public static function vragen(): array
    {
        $act = self::ACTUEEL;

        return [
            new Vraag('voorraad.beschikbaar', self::BRON, 'Hoeveel materieel is beschikbaar (per depot, per status)?',
                [['voorraad', 'beschikbaar', 'beschikbaarheid', 'op de plank', 'materieel', 'staat er', 'in service', 'in reparatie']],
                ['depot', 'hoeveel', 'status'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($act) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(d.naam, m.depot_naam, m.depot_nummer) AS depot, m.status_code, COUNT(*) AS n FROM materieel m LEFT JOIN depots d ON d.depot_nummer = m.depot_nummer WHERE $act AND $w GROUP BY depot, m.status_code ORDER BY depot, n DESC", $bind);
                    $sub = [];
                    foreach ($rijen as $x) {
                        $sub[self::STATUS[$x['status_code']] ?? $x['status_code']] = ($sub[self::STATUS[$x['status_code']] ?? $x['status_code']] ?? 0) + (int) $x['n'];
                    }

                    return Resultaat::tabel(['Depot', 'Status', 'Aantal'], array_map(fn ($x) => [$x['depot'], self::STATUS[$x['status_code']] ?? $x['status_code'], (int) $x['n']], $rijen), 'Materieel per depot en status uit de laatste materieellijst in de Voorraad tool.', $sub);
                }),

            new Vraag('voorraad.bezetting', self::BRON, 'Wat is de bezettingsgraad per depot (verhuurd t.o.v. totaal)?',
                [['bezetting', 'bezettingsgraad', 'benutting', 'verhuurd percentage', 'utilisatie']],
                ['depot', 'materieel', 'voorraad'], ['depot'], 'manager', 'geen',
                function (DataBrug $b, array $p) use ($act) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(d.naam, m.depot_naam, m.depot_nummer) AS depot, COUNT(*) AS totaal, SUM(CASE WHEN m.status_code='on_hire' THEN 1 ELSE 0 END) AS verhuurd, SUM(CASE WHEN m.status_code='available' THEN 1 ELSE 0 END) AS beschikbaar FROM materieel m LEFT JOIN depots d ON d.depot_nummer = m.depot_nummer WHERE $act AND $w GROUP BY depot ORDER BY totaal DESC", $bind);

                    return Resultaat::tabel(['Depot', 'Totaal', 'Verhuurd', 'Beschikbaar', 'Bezetting'], array_map(fn ($x) => [$x['depot'], (int) $x['totaal'], (int) $x['verhuurd'], (int) $x['beschikbaar'], round(100 * $x['verhuurd'] / max(1, $x['totaal']), 1).' %'], $rijen), 'Verhuurd (on hire) gedeeld door totaal per depot, laatste materieellijst.');
                }),

            new Vraag('voorraad.minimum', self::BRON, 'Waar zitten we onder de minimumvoorraad?',
                [['minimum', 'minimumvoorraad', 'minimale voorraad', 'onder de', 'tekort', 'te weinig']],
                ['voorraad', 'depot'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($act) {
                    $bind = [];
                    $w = empty($p['depot']) ? '1=1' : Depots::likeClause($p['depot'], 'd.naam', $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(d.naam, mv.depot_nummer) AS depot, mv.subgroep_nr, mv.subgroep_naam, mv.minimum, (SELECT COUNT(*) FROM materieel m WHERE m.depot_nummer = mv.depot_nummer AND m.subgroep_nr = mv.subgroep_nr AND m.status_code='available' AND $act) AS beschikbaar FROM min_voorraad mv LEFT JOIN depots d ON d.depot_nummer = mv.depot_nummer WHERE $w ORDER BY depot", $bind);
                    $rijen = array_values(array_filter($rijen, fn ($x) => (int) $x['beschikbaar'] < (int) $x['minimum']));

                    return Resultaat::tabel(['Depot', 'Subgroep', 'Omschrijving', 'Minimum', 'Beschikbaar', 'Tekort'], array_map(fn ($x) => [$x['depot'], $x['subgroep_nr'], $x['subgroep_naam'], (int) $x['minimum'], (int) $x['beschikbaar'], (int) $x['minimum'] - (int) $x['beschikbaar']], $rijen), 'Subgroepen waar het aantal beschikbare machines onder het ingestelde minimum ligt.');
                }),

            new Vraag('voorraad.langliggers', self::BRON, 'Welk materieel staat al lang stil (langliggers)?',
                [['langligger', 'langliggers', 'lang stil', 'staat stil', 'niet verhuurd', 'lang niet uit', 'stilstaand']],
                ['voorraad', 'materieel', 'dagen'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($act) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(d.naam, m.depot_naam) AS depot, m.subgroep_naam, COUNT(*) AS n, MIN(m.laatste_uithuur) AS langst FROM materieel m LEFT JOIN depots d ON d.depot_nummer = m.depot_nummer WHERE $act AND $w AND m.status_code='available' AND (m.laatste_uithuur IS NULL OR m.laatste_uithuur < date('now','-90 day')) GROUP BY depot, m.subgroep_naam ORDER BY n DESC LIMIT 100", $bind);

                    return Resultaat::tabel(['Depot', 'Subgroep', 'Aantal', 'Langst stil sinds'], array_map(fn ($x) => [$x['depot'], $x['subgroep_naam'], (int) $x['n'], $x['langst'] ?: 'onbekend'], $rijen), 'Beschikbaar materieel dat meer dan 90 dagen niet is uitgehuurd (laatste uithuur).', ['Totaal' => array_sum(array_column($rijen, 'n'))]);
                }),

            new Vraag('voorraad.reserveringen', self::BRON, 'Welke reserveringen komen eraan (komende 3 weken)?',
                [['reservering', 'reserveringen', 'gereserveerd', 'quote', 'quotes', 'komt eraan', 'komende']],
                ['voorraad', 'depot', 'weken'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = empty($p['depot']) ? '1=1' : Depots::likeClause($p['depot'], 'COALESCE(d.naam, r.depot_naam)', $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(d.naam, r.depot_naam, r.depot_nummer) AS depot, r.subgroep_nr, r.omschrijving, SUM(r.aantal) AS gevraagd, MIN(r.startdatum) AS eerste FROM reserveringen r LEFT JOIN depots d ON d.depot_nummer = r.depot_nummer WHERE r.startdatum BETWEEN date('now') AND date('now','+21 day') AND r.upload_id IN (SELECT id FROM uploads WHERE type='reserveringen' AND actueel=1) AND $w GROUP BY depot, r.subgroep_nr ORDER BY eerste LIMIT 150", $bind);

                    return Resultaat::tabel(['Depot', 'Subgroep', 'Omschrijving', 'Gevraagd', 'Eerste start'], array_map(fn ($x) => [$x['depot'], $x['subgroep_nr'], $x['omschrijving'], (int) $x['gevraagd'], $x['eerste']], $rijen), 'Reserveringen met startdatum binnen 21 dagen (laatste reserveringenlijst).');
                }),

            new Vraag('voorraad.aanvragen', self::BRON, 'Hoeveel aanvraagmails zijn er verstuurd, en naar welke depots?',
                [['aanvraag', 'aanvragen', 'aanvraagmail', 'aanvraagmails', 'gevraagd aan', 'verstuurd naar']],
                ['voorraad', 'depot', 'machines'], ['periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) {
                    $rijen = $b->select(self::BRON, "SELECT depot_naam, status, COUNT(*) AS n, SUM(aantal_machines) AS machines FROM aanvragen WHERE created_at >= ? AND created_at <= ? GROUP BY depot_naam, status ORDER BY n DESC", [$p['periode']['van_s'].' 00:00:00', $p['periode']['tm_s'].' 23:59:59']);

                    return Resultaat::tabel(['Depot (ontvanger)', 'Status', 'Aanvragen', 'Machines'], array_map(fn ($x) => [$x['depot_naam'], $x['status'], (int) $x['n'], (int) $x['machines']], $rijen), 'Aanvraagmails uit de Voorraad tool in '.$p['periode']['label'].'.', ['Totaal' => array_sum(array_column($rijen, 'n'))]);
                }),

            new Vraag('voorraad.orders', self::BRON, 'Welke orderregels zijn nog niet toegewezen (per contract)?',
                [['orderregel', 'orderregels', 'niet toegewezen', 'not allocated', 'toe te wijzen', 'open orders', 'openstaande orders']],
                ['voorraad', 'contract'], [], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $rijen = $b->select(self::BRON, "SELECT o.contract_nr, o.project_omschrijving, COUNT(*) AS regels, SUM(o.aantal) AS stuks, MIN(o.verhuurdatum) AS eerste FROM order_regels o WHERE o.status_code = 'not_allocated' AND o.upload_id IN (SELECT id FROM uploads WHERE actueel = 1) GROUP BY o.contract_nr ORDER BY stuks DESC LIMIT 50");

                    return Resultaat::tabel(['Contract', 'Omschrijving', 'Regels', 'Stuks', 'Eerste verhuurdatum'], array_map(fn ($x) => [$x['contract_nr'], $x['project_omschrijving'], (int) $x['regels'], (int) $x['stuks'], $x['eerste']], $rijen), 'Orderregels met status "niet toegekend" in de actuele contract-/projectlijsten.');
                }),
        ];
    }
}
