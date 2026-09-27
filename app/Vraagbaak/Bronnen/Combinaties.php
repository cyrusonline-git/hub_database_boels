<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Cross-app vragen: meerdere bronnen combineren in PHP (op depotnaam of machinenummer).
 * De bron-notatie 'tankapp+inhuur+projectschade' bepaalt welke apps de gebruiker moet mogen zien.
 */
class Combinaties
{
    /** Depotnamen uit CORE (org_depots) als kapstok voor de scorekaart. */
    private static function coreDepots(?array $keuze): array
    {
        $alle = Depots::lijst();
        if ($keuze) {
            $alle = array_values(array_filter($alle, fn ($d) => $keuze['type'] === 'area' ? mb_strtolower((string) $d['area']) === mb_strtolower($keuze['naam']) : $d['naam'] === $keuze['naam']));
        }

        return $alle;
    }

    /** Waarde uit een lijst rijen [naam => waarde] zoeken op depot-termen. */
    private static function zoek(array $rijen, array $depot, string $naamKol, string $waardeKol): float
    {
        $som = 0.0;
        foreach ($rijen as $r) {
            $n = mb_strtolower((string) $r[$naamKol]);
            foreach ($depot['termen'] as $t) {
                if (str_contains($n, $t)) {
                    $som += (float) $r[$waardeKol];
                    break;
                }
            }
        }

        return $som;
    }

    public static function vragen(): array
    {
        return [
            new Vraag('combi.scorekaart', 'tankapp+inhuur+projectschade+voorraad', 'Depot-scorekaart: tanken, inhuur, schade en voorraad in één tabel',
                [['scorekaart', 'overzicht per depot', 'alles per depot', 'dashboard per depot', 'hoe doet', 'hoe staat', 'totaalbeeld', 'vergelijk depots', 'vergelijking']],
                ['depot', 'depots', 'vestiging'], ['depot', 'periode'], 'manager', 'maand',
                function (DataBrug $b, array $p) {
                    $van = $p['periode']['van_s'];
                    $tm = $p['periode']['tm_s'];
                    $tank = $b->select('tankapp', "SELECT d.name AS naam, ROUND(SUM(a.liters),1) AS liters FROM aftankingen a JOIN depots d ON a.depot_id = d.id WHERE a.created_at BETWEEN ? AND ? AND d.name != 'BLS' GROUP BY d.name", [$van.' 00:00:00', $tm.' 23:59:59']);
                    $inhuur = $b->select('inhuur', "SELECT d.name AS naam, COUNT(*) AS regels, ROUND(SUM(COALESCE(r.hire_rate_week,0)*COALESCE(r.quantity,1)),2) AS kosten FROM rentals r JOIN depots d ON r.depot_id = d.id WHERE r.status='IN HUUR' GROUP BY d.name");
                    $schade = $b->select('projectschade', "SELECT d.naam AS naam, COUNT(*) AS n, ROUND(SUM(CASE WHEN di.bd_status='Afgehandeld' THEN COALESCE(di.doorbelast_bedrag, di.totaal_schadebedrag, 0) ELSE 0 END),2) AS doorbelast FROM damage_items di JOIN damage_headers dh ON di.damage_header_id = dh.id JOIN depots d ON dh.depot_id = d.id WHERE dh.created_at BETWEEN ? AND ? GROUP BY d.naam", [$van.' 00:00:00', $tm.' 23:59:59']);
                    $voorraad = $b->select('voorraad', "SELECT COALESCE(d.naam, m.depot_naam) AS naam, COUNT(*) AS totaal, SUM(CASE WHEN m.status_code='available' THEN 1 ELSE 0 END) AS beschikbaar, SUM(CASE WHEN m.status_code='on_hire' THEN 1 ELSE 0 END) AS verhuurd FROM materieel m LEFT JOIN depots d ON d.depot_nummer = m.depot_nummer WHERE m.upload_id IN (SELECT id FROM uploads WHERE type='materieel' AND actueel=1) GROUP BY naam");
                    $rijen = [];
                    foreach (self::coreDepots($p['depot'] ?? null) as $d) {
                        $tot = self::zoek($voorraad, $d, 'naam', 'totaal');
                        $rijen[] = [$d['naam'], self::zoek($tank, $d, 'naam', 'liters'), (int) self::zoek($inhuur, $d, 'naam', 'regels'), self::zoek($inhuur, $d, 'naam', 'kosten'), (int) self::zoek($schade, $d, 'naam', 'n'), self::zoek($schade, $d, 'naam', 'doorbelast'), (int) self::zoek($voorraad, $d, 'naam', 'beschikbaar'), $tot > 0 ? round(100 * self::zoek($voorraad, $d, 'naam', 'verhuurd') / $tot, 1).' %' : '-'];
                    }

                    return Resultaat::tabel(['Depot', 'Liters getankt', 'Inhuur regels', 'Inhuur €/week', 'Schades', 'Doorbelast €', 'Voorraad beschikbaar', 'Bezetting'], $rijen, 'Combinatie van Tankapp (liters in '.$p['periode']['label'].'), Inhuur (lopend), Projectschade (schades in '.$p['periode']['label'].') en Voorraad tool (laatste lijst), gekoppeld op depotnaam uit CORE.');
                }),

            new Vraag('combi.kosten', 'tankapp+inhuur+projectschade', 'Kosten per depot: inhuur, schadeverlies en brandstof',
                [['kosten per depot', 'kosten per vestiging', 'wat kost', 'totale kosten', 'uitgaven']],
                ['depot', 'inhuur', 'schade', 'brandstof'], ['depot', 'periode'], 'manager', 'maand',
                function (DataBrug $b, array $p) {
                    $van = $p['periode']['van_s'];
                    $tm = $p['periode']['tm_s'];
                    $prijs = (float) str_replace(',', '.', (string) (config('boels.vraagbaak_dieselprijs') ?? 1.60));
                    $tank = $b->select('tankapp', "SELECT d.name AS naam, ROUND(SUM(a.liters),1) AS liters FROM aftankingen a JOIN depots d ON a.depot_id = d.id WHERE a.created_at BETWEEN ? AND ? AND d.name != 'BLS' AND a.status IN ('niet_doorbelast','eigen_gebruik') GROUP BY d.name", [$van.' 00:00:00', $tm.' 23:59:59']);
                    $weken = max(1, round((strtotime($tm) - strtotime($van)) / 604800, 1));
                    $inhuur = $b->select('inhuur', "SELECT d.name AS naam, ROUND(SUM(COALESCE(r.hire_rate_week,0)*COALESCE(r.quantity,1)),2) AS kosten FROM rentals r JOIN depots d ON r.depot_id = d.id WHERE r.status='IN HUUR' GROUP BY d.name");
                    $schade = $b->select('projectschade', "SELECT d.naam AS naam, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS verlies FROM damage_items di JOIN damage_headers dh ON di.damage_header_id = dh.id JOIN depots d ON dh.depot_id = d.id WHERE dh.created_at BETWEEN ? AND ? AND di.bd_status IN ('Geen schade','Intern afgesloten') GROUP BY d.naam", [$van.' 00:00:00', $tm.' 23:59:59']);
                    $rijen = [];
                    foreach (self::coreDepots($p['depot'] ?? null) as $d) {
                        $l = self::zoek($tank, $d, 'naam', 'liters');
                        $i = round(self::zoek($inhuur, $d, 'naam', 'kosten') * $weken, 2);
                        $s = self::zoek($schade, $d, 'naam', 'verlies');
                        $rijen[] = [$d['naam'], round($l * $prijs, 2), $i, $s, round($l * $prijs + $i + $s, 2)];
                    }
                    usort($rijen, fn ($a, $b) => $b[4] <=> $a[4]);

                    return Resultaat::tabel(['Depot', 'Brandstof niet doorbelast (€)', 'Inhuur ('.$weken.' wk, €)', 'Schadeverlies (€)', 'Totaal (€)'], $rijen, 'Schatting: niet-doorbelaste liters × € '.number_format($prijs, 2, ',', '.').'/l (instelling), lopende inhuurkosten × aantal weken in de periode, en schades die als coulance/intern afgesloten zijn geboekt in '.$p['periode']['label'].'.');
                }),

            new Vraag('combi.tank_schade', 'tankapp+projectschade', 'Welke machines hebben getankt én een schade (periode)?',
                [['getankt en schade', 'schade en getankt', 'tankbeurt en schade', 'zowel', 'en een schade', 'met schade getankt']],
                ['machine', 'machines'], ['depot', 'periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) {
                    $van = $p['periode']['van_s'];
                    $tm = $p['periode']['tm_s'];
                    $bind = [$van.' 00:00:00', $tm.' 23:59:59'];
                    $w = empty($p['depot']) ? '1=1' : Depots::likeClause($p['depot'], 'd.name', $bind);
                    $tank = $b->select('tankapp', "SELECT a.machine_number AS machine, d.name AS vestiging, COUNT(*) AS keer, ROUND(SUM(a.liters),1) AS liters FROM aftankingen a JOIN depots d ON a.depot_id = d.id WHERE a.created_at BETWEEN ? AND ? AND $w AND a.machine_number != '' GROUP BY a.machine_number, d.name", $bind);
                    $schade = $b->select('projectschade', "SELECT COALESCE(di.qr_code, ma.artikelnummer) AS machine, COUNT(*) AS n, ROUND(SUM(COALESCE(di.totaal_schadebedrag,0)),2) AS bedrag, MAX(di.bd_status) AS status FROM damage_items di JOIN damage_headers dh ON di.damage_header_id = dh.id LEFT JOIN machine_articles ma ON di.machine_article_id = ma.id WHERE dh.created_at BETWEEN ? AND ? GROUP BY machine", [$van.' 00:00:00', $tm.' 23:59:59']);
                    $perMachine = [];
                    foreach ($schade as $s) {
                        if ($s['machine']) {
                            $perMachine[preg_replace('/\D+/', '', (string) $s['machine'])] = $s;
                        }
                    }
                    $rijen = [];
                    foreach ($tank as $t) {
                        $nr = preg_replace('/\D+/', '', (string) $t['machine']);
                        if ($nr !== '' && isset($perMachine[$nr])) {
                            $s = $perMachine[$nr];
                            $rijen[] = [$t['machine'], $t['vestiging'], (int) $t['keer'], (float) $t['liters'], (int) $s['n'], (float) $s['bedrag'], $s['status']];
                        }
                    }

                    return Resultaat::tabel(['Machine', 'Vestiging (tank)', 'Tankbeurten', 'Liters', 'Schades', 'Schadebedrag (€)', 'Status'], $rijen, 'Machinenummers die in '.$p['periode']['label'].' zowel in de Tankapp als in Projectschade voorkomen (gekoppeld op nummer).');
                }),

            new Vraag('combi.dienst_melding', 'spoedverhuur', 'Wie had dienst op datum X (bij een melding)?',
                [['wie had dienst', 'had dienst', 'dienst op', 'wie was er', 'wie stond er']],
                ['melding', 'nacht', 'weekend', 'datum'], ['periode'], 'iedereen', 'week',
                function (DataBrug $b, array $p) {
                    $rijen = $b->select('spoedverhuur', "SELECT w.weeknummer, w.van, w.tm, ds.naam AS dienst, COALESCE(m.naam, t.rooster_naam, '—') AS medewerker, COALESCE(m.telefoon, m.telefoon_handmatig, '') AS telefoon, t.dag_van, t.dag_tm FROM toewijzingen t JOIN rooster_weken w ON t.rooster_week_id = w.id JOIN dienst_soorten ds ON t.dienst_soort_id = ds.id LEFT JOIN medewerkers m ON t.medewerker_id = m.id WHERE w.van <= ? AND w.tm >= ? ORDER BY w.van, ds.volgorde", [$p['periode']['tm_s'], $p['periode']['van_s']]);
                    $dagen = ['', 'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

                    return Resultaat::tabel(['Week', 'Dienst', 'Medewerker', 'Telefoon', 'Dagen'], array_map(fn ($x) => ['Week '.$x['weeknummer'].' ('.$x['van'].' t/m '.$x['tm'].')', $x['dienst'], $x['medewerker'], $x['telefoon'], ($x['dag_van'] == 1 && $x['dag_tm'] == 7) ? 'hele week' : $dagen[$x['dag_van']].'–'.$dagen[$x['dag_tm']]], $rijen), 'Dienstdoenden (effectief rooster) in de week/periode '.$p['periode']['label'].'.');
                }),

            new Vraag('combi.inhuur_vs_voorraad', 'inhuur+voorraad', 'Huren we in wat we zelf beschikbaar hebben (inhuur vs eigen voorraad)?',
                [['inhuur vs voorraad', 'zelf beschikbaar', 'zelf op de plank', 'huren we in terwijl', 'eigen voorraad', 'onnodige inhuur', 'onnodig ingehuurd']],
                ['inhuur', 'voorraad', 'subgroep'], ['depot'], 'manager', 'geen',
                function (DataBrug $b, array $p) {
                    $bind = [];
                    $w = empty($p['depot']) ? '1=1' : Depots::likeClause($p['depot'], 'd.name', $bind);
                    $inhuur = $b->select('inhuur', "SELECT d.name AS vestiging, r.subgroup_number AS subgroep, MAX(r.article_name) AS artikel, SUM(COALESCE(r.quantity,1)) AS stuks FROM rentals r JOIN depots d ON r.depot_id = d.id WHERE r.status='IN HUUR' AND r.subgroup_number IS NOT NULL AND r.subgroup_number != '' AND $w GROUP BY d.name, r.subgroup_number", $bind);
                    $voorraad = $b->select('voorraad', "SELECT COALESCE(d.naam, m.depot_naam) AS depot, m.subgroep_nr, COUNT(*) AS beschikbaar FROM materieel m LEFT JOIN depots d ON d.depot_nummer = m.depot_nummer WHERE m.status_code='available' AND m.upload_id IN (SELECT id FROM uploads WHERE type='materieel' AND actueel=1) GROUP BY depot, m.subgroep_nr");
                    $vk = [];
                    foreach ($voorraad as $v) {
                        $vk[trim((string) $v['subgroep_nr'])][] = $v;
                    }
                    $rijen = [];
                    foreach ($inhuur as $i) {
                        $sg = trim((string) $i['subgroep']);
                        if (! isset($vk[$sg])) {
                            continue;
                        }
                        $eigen = 0;
                        $elders = 0;
                        $termen = Depots::termenUitNaam((string) $i['vestiging']);
                        foreach ($vk[$sg] as $v) {
                            $zelfde = false;
                            foreach ($termen as $t) {
                                if (str_contains(mb_strtolower((string) $v['depot']), $t)) {
                                    $zelfde = true;
                                }
                            }
                            $zelfde ? $eigen += (int) $v['beschikbaar'] : $elders += (int) $v['beschikbaar'];
                        }
                        if ($eigen + $elders > 0) {
                            $rijen[] = [$i['vestiging'], $sg, $i['artikel'], (int) $i['stuks'], $eigen, $elders];
                        }
                    }
                    usort($rijen, fn ($a, $b) => ($b[4] + $b[5]) <=> ($a[4] + $a[5]));

                    return Resultaat::tabel(['Vestiging (inhuur)', 'Subgroep', 'Artikel', 'Ingehuurd (stuks)', 'Beschikbaar eigen depot', 'Beschikbaar andere depots'], $rijen, 'Lopende inhuur per subgroep naast het aantal beschikbare eigen machines van dezelfde subgroep (laatste materieellijst).');
                }),
        ];
    }
}
