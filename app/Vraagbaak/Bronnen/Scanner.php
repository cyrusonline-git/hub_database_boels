<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Scanner (SQLite): projects (project_name, is_active), locations (location_number, location_name, project_id, project_name),
 * location_items (location_id, machine_number, description, quantity, status uitgezet|terug, damaged, placed_at, returned_at).
 * Geen depot: scoping via project. "Vermissing" = uitgezet en nog niet terug.
 */
class Scanner
{
    private const BRON = 'scanner';
    private const FROM = 'FROM location_items li JOIN locations l ON li.location_id = l.id';

    private static function projectW(array $p, array &$bind): string
    {
        if (empty($p['naam'])) {
            return '1=1';
        }
        $bind[] = '%'.$p['naam'].'%';

        return 'l.project_name LIKE ?';
    }

    public static function vragen(): array
    {
        $f = self::FROM;

        return [
            new Vraag('scan.op_locatie', self::BRON, 'Hoeveel items staan er nog op locatie (per project)?',
                [['op locatie', 'nog uit', 'uitgezet', 'staat nog', 'staan nog', 'nog niet terug', 'niet retour', 'vermist', 'vermissing', 'vermissingen', 'kwijt', 'scanner']],
                ['project', 'items', 'materieel'], ['naam'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::projectW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT l.project_name AS project, COUNT(*) AS items, SUM(COALESCE(li.quantity,1)) AS stuks, COUNT(DISTINCT l.id) AS locaties, MIN(substr(li.placed_at,1,10)) AS oudste, SUM(COALESCE(li.damaged,0)) AS beschadigd $f WHERE li.status = 'uitgezet' AND $w GROUP BY l.project_name ORDER BY items DESC", $bind);

                    return Resultaat::tabel(['Project', 'Items op locatie', 'Stuks', 'Locaties', 'Oudste plaatsing', 'Beschadigd'], array_map(fn ($x) => [$x['project'], (int) $x['items'], (int) $x['stuks'], (int) $x['locaties'], $x['oudste'], (int) $x['beschadigd']], $rijen), 'Items met status "uitgezet" (geplaatst, nog niet retour) in de Scanner-app.', ['Totaal items' => array_sum(array_column($rijen, 'items'))]);
                }),

            new Vraag('scan.locaties', self::BRON, 'Welke locaties hebben de meeste items nog open?',
                [['locatie', 'locaties', 'plek', 'plekken'], ['meeste', 'top', 'welke', 'grootste']],
                [], ['naam', 'top'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [];
                    $w = self::projectW($p, $bind);
                    $n = (int) ($p['top'] ?: 20);
                    $rijen = $b->select(self::BRON, "SELECT l.project_name AS project, l.location_number AS nr, COALESCE(l.location_name,'') AS naam, COUNT(*) AS items, MIN(substr(li.placed_at,1,10)) AS sinds $f WHERE li.status = 'uitgezet' AND $w GROUP BY l.id ORDER BY items DESC LIMIT $n", $bind);

                    return Resultaat::tabel(['Project', 'Locatie', 'Omschrijving', 'Items open', 'Sinds'], array_map(fn ($x) => [$x['project'], $x['nr'], $x['naam'], (int) $x['items'], $x['sinds']], $rijen), "Top $n locaties op aantal items dat nog niet retour is.");
                }),

            new Vraag('scan.bewegingen', self::BRON, 'Hoeveel is er geplaatst en retour gescand (periode)?',
                [['geplaatst', 'plaatsingen', 'retour gescand', 'gescand', 'scans', 'bewegingen', 'ingescand', 'uitgescand', 'teruggehaald']],
                ['scanner', 'project'], ['periode', 'naam'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) use ($f) {
                    $bind = [$p['periode']['van_s'].' 00:00:00', $p['periode']['tm_s'].' 23:59:59', $p['periode']['van_s'].' 00:00:00', $p['periode']['tm_s'].' 23:59:59'];
                    $w = self::projectW($p, $bind);
                    $rijen = $b->select(self::BRON, "SELECT l.project_name AS project, SUM(CASE WHEN li.placed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS geplaatst, SUM(CASE WHEN li.returned_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS retour $f WHERE $w GROUP BY l.project_name HAVING geplaatst > 0 OR retour > 0 ORDER BY geplaatst DESC", $bind);

                    return Resultaat::tabel(['Project', 'Geplaatst', 'Retour'], array_map(fn ($x) => [$x['project'], (int) $x['geplaatst'], (int) $x['retour']], $rijen), 'Plaatsingen en retourscans in '.$p['periode']['label'].'.', ['Geplaatst' => array_sum(array_column($rijen, 'geplaatst')), 'Retour' => array_sum(array_column($rijen, 'retour'))]);
                }),

            new Vraag('scan.machine', self::BRON, 'Waar is machine 1234567 (laatste scan)?',
                [['waar is', 'waar staat', 'laatste scan', 'locatie van machine', 'gescand', 'scanner']],
                ['machine'], ['machine'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) use ($f) {
                    if (empty($p['machine'])) {
                        return Resultaat::tekst('Noem het machinenummer, bijvoorbeeld: waar is machine 7590019510?');
                    }
                    $rijen = $b->select(self::BRON, "SELECT l.project_name AS project, l.location_number AS locatie, COALESCE(l.location_name,'') AS naam, li.status, substr(li.placed_at,1,16) AS geplaatst, substr(COALESCE(li.returned_at,''),1,16) AS retour, li.damaged $f WHERE li.machine_number LIKE ? ORDER BY li.placed_at DESC LIMIT 20", ['%'.$p['machine'].'%']);

                    return Resultaat::tabel(['Project', 'Locatie', 'Omschrijving', 'Status', 'Geplaatst', 'Retour', 'Schade'], array_map(fn ($x) => [$x['project'], $x['locatie'], $x['naam'], $x['status'] === 'uitgezet' ? 'nog op locatie' : 'retour', $x['geplaatst'], $x['retour'] ?: '-', $x['damaged'] ? 'ja' : ''], $rijen), 'Scans van machine '.$p['machine'].' (nieuwste eerst).');
                }),
        ];
    }
}
