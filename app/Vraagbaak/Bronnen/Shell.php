<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Shell-klantportaal (MySQL, prefix shell_): shell_current_rentals (item_number, item_description, quantity, hire_date, est_rtd,
 * weekly_rate/week_rate, project_title, boels_depot (vrije tekst), status active|returned, returned_at), shell_machine_hours (item_number, date, operating_hours),
 * shell_actions (type, status, deadline_at). Alleen voor Boels-rollen (bron-toegang via CORE-app 'shell').
 */
class Shell
{
    private const BRON = 'shell';

    private static function depotW(array $p, array &$bind): string
    {
        return empty($p['depot']) ? '1=1' : Depots::likeClause($p['depot'], 'boels_depot', $bind);
    }

    public static function vragen(): array
    {
        return [
            new Vraag('shell.uitstaand', self::BRON, 'Shell: wat staat er uit per Boels-depot (omzet per week)?',
                [['shell'], ['uitstaand', 'staat uit', 'in huur', 'actief', 'verhuurd', 'omzet', 'lopend']],
                ['depot', 'per week'], ['depot'], 'manager', 'geen',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(boels_depot,'-') AS depot, COUNT(*) AS items, SUM(quantity) AS stuks, ROUND(SUM(COALESCE(weekly_rate, week_rate, 0) * COALESCE(quantity,1)),2) AS omzet_pw FROM shell_current_rentals WHERE status = 'active' AND $w GROUP BY boels_depot ORDER BY omzet_pw DESC", $bind);

                    return Resultaat::tabel(['Boels-depot', 'Items', 'Stuks', 'Omzet per week (€)'], array_map(fn ($x) => [$x['depot'], (int) $x['items'], (int) $x['stuks'], (float) $x['omzet_pw']], $rijen), 'Actieve huurregels in het Shell-portaal per Boels-depot; omzet = weektarief × aantal.', ['Items' => array_sum(array_column($rijen, 'items'))]);
                }),

            new Vraag('shell.overdue', self::BRON, 'Shell: welke items zijn over de verwachte einddatum?',
                [['shell'], ['over datum', 'einddatum', 'overdue', 'te laat', 'verlopen', 'over de datum']],
                [], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT item_number, item_description, project_title, COALESCE(boels_depot,'-') AS depot, est_rtd, DATEDIFF(CURDATE(), est_rtd) AS dagen FROM shell_current_rentals WHERE status='active' AND est_rtd IS NOT NULL AND est_rtd < CURDATE() AND $w ORDER BY dagen DESC LIMIT 100", $bind);

                    return Resultaat::tabel(['Item', 'Omschrijving', 'Project', 'Depot', 'Verwacht einde', 'Dagen over'], array_map(fn ($x) => [$x['item_number'], $x['item_description'], $x['project_title'], $x['depot'], $x['est_rtd'], (int) $x['dagen']], $rijen), 'Actieve items in het Shell-portaal waarvan de verwachte einddatum voorbij is.');
                }),

            new Vraag('shell.stilstand', self::BRON, 'Shell: welke machines staan stil (geen draaiuren in 30 dagen)?',
                [['shell'], ['stilstand', 'staan stil', 'staat stil', 'geen draaiuren', 'niet gebruikt', 'idle']],
                ['draaiuren', 'machines'], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT cr.item_number, cr.item_description, cr.project_title, COALESCE(cr.boels_depot,'-') AS depot, cr.hire_date, COALESCE(SUM(mh.operating_hours),0) AS uren FROM shell_current_rentals cr LEFT JOIN shell_machine_hours mh ON mh.item_number = cr.item_number AND mh.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) WHERE cr.status='active' AND $w GROUP BY cr.id HAVING uren = 0 ORDER BY cr.hire_date LIMIT 100", $bind);

                    return Resultaat::tabel(['Item', 'Omschrijving', 'Project', 'Depot', 'In huur sinds'], array_map(fn ($x) => [$x['item_number'], $x['item_description'], $x['project_title'], $x['depot'], $x['hire_date']], $rijen), 'Actieve Shell-machines zonder draaiuren in de laatste 30 dagen.', ['Machines' => count($rijen)]);
                }),

            new Vraag('shell.langlopers', self::BRON, 'Shell: wat staat al langer dan 6 maanden uit?',
                [['shell'], ['langloper', 'langlopers', 'langer dan', 'lang uit', 'maanden uit', '6 maanden']],
                [], ['depot'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(boels_depot,'-') AS depot, project_title, item_number, item_description, hire_date, DATEDIFF(CURDATE(), hire_date) AS dagen FROM shell_current_rentals WHERE status='active' AND hire_date < DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND $w ORDER BY dagen DESC LIMIT 100", $bind);

                    return Resultaat::tabel(['Depot', 'Project', 'Item', 'Omschrijving', 'In huur sinds', 'Dagen'], array_map(fn ($x) => [$x['depot'], $x['project_title'], $x['item_number'], $x['item_description'], $x['hire_date'], (int) $x['dagen']], $rijen), 'Actieve Shell-huur die langer dan 6 maanden loopt.');
                }),

            new Vraag('shell.signalen', self::BRON, 'Shell: welke signalen staan open?',
                [['shell'], ['signaal', 'signalen', 'acties', 'openstaande acties', 'escalatie']],
                [], [], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $rijen = $b->select(self::BRON, "SELECT type, status, COUNT(*) AS n, ROUND(AVG(DATEDIFF(CURDATE(), created_at)),1) AS leeftijd, SUM(deadline_at IS NOT NULL AND deadline_at < CURDATE()) AS over_deadline FROM shell_actions WHERE status <> 'closed' GROUP BY type, status ORDER BY n DESC");

                    return Resultaat::tabel(['Type', 'Status', 'Aantal', 'Gem. leeftijd (dagen)', 'Over deadline'], array_map(fn ($x) => [$x['type'], $x['status'], (int) $x['n'], (float) $x['leeftijd'], (int) $x['over_deadline']], $rijen), 'Open signalen (acties) in het Shell-portaal.');
                }),

            new Vraag('shell.retouren', self::BRON, 'Shell: hoeveel items kwamen retour (periode)?',
                [['shell'], ['retour', 'retouren', 'teruggekomen', 'afgemeld', 'terug']],
                [], ['depot', 'periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) {
                    $bind = [$p['periode']['van_s'], $p['periode']['tm_s'].' 23:59:59'];
                    $w = self::depotW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(boels_depot,'-') AS depot, COUNT(*) AS retouren, ROUND(AVG(DATEDIFF(returned_at, hire_date)),1) AS gem_dagen FROM shell_current_rentals WHERE status='returned' AND returned_at BETWEEN ? AND ? AND $w GROUP BY boels_depot ORDER BY retouren DESC", $bind);

                    return Resultaat::tabel(['Depot', 'Retouren', 'Gem. huurduur (dagen)'], array_map(fn ($x) => [$x['depot'], (int) $x['retouren'], (float) $x['gem_dagen']], $rijen), 'Items in het Shell-portaal die retour zijn gemeld in '.$p['periode']['label'].'.');
                }),
        ];
    }
}
