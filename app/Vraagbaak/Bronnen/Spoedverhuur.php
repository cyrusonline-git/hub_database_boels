<?php

namespace App\Vraagbaak\Bronnen;

use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Resultaat;
use App\Vraagbaak\Vraag;

/**
 * Spoedverhuur (SQLite): toewijzingen (rooster_week_id, dienst_soort_id, medewerker_id, rooster_naam, dag_van, dag_tm, oorsprong),
 * rooster_weken (jaar, weeknummer, van, tm), dienst_soorten (naam, betaald, vast, vaste_naam), medewerkers (naam, telefoon),
 * ruilingen (type, status, van_medewerker_id, naar_medewerker_id, created_at, bevestigd_op), maandoverzichten (jaar, maand, totaal_bedrag, aantal_regels).
 */
class Spoedverhuur
{
    private const BRON = 'spoedverhuur';
    private const FROM = 'FROM toewijzingen t JOIN rooster_weken w ON t.rooster_week_id = w.id JOIN dienst_soorten ds ON t.dienst_soort_id = ds.id LEFT JOIN medewerkers m ON t.medewerker_id = m.id';

    public static function vragen(): array
    {
        $f = self::FROM;

        return [
            new Vraag('spoed.dienst', self::BRON, 'Wie heeft er dienst (deze week / volgende week / week 40)?',
                [['dienst', 'diensten', 'spoeddienst', 'storingsdienst', 'spoedverhuur', 'bereikbaarheid', 'piket', 'wie heeft dienst', 'wie staat er']],
                ['wie', 'week'], ['periode'], 'iedereen', 'week',
                function (DataBrug $b, array $p) use ($f) {
                    $rijen = $b->select(self::BRON, "SELECT w.jaar, w.weeknummer, w.van, w.tm, ds.naam AS dienst, COALESCE(m.naam, t.rooster_naam, '—') AS medewerker, COALESCE(m.telefoon, m.telefoon_handmatig, '') AS telefoon, t.dag_van, t.dag_tm, t.oorsprong $f WHERE w.tm >= ? AND w.van <= ? ORDER BY w.van, ds.volgorde, t.dag_van", [$p['periode']['van_s'], $p['periode']['tm_s']]);
                    $dagen = ['', 'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];
                    $out = array_map(fn ($x) => ['Week '.$x['weeknummer'].' ('.substr($x['van'], 8, 2).'-'.substr($x['van'], 5, 2).')', $x['dienst'], $x['medewerker'].(($x['dag_van'] != 1 || $x['dag_tm'] != 7) ? ' ('.$dagen[$x['dag_van']].'–'.$dagen[$x['dag_tm']].')' : ''), $x['telefoon'], $x['oorsprong'] === 'rooster' ? '' : $x['oorsprong']], $rijen);
                    $vast = $b->select(self::BRON, "SELECT naam, vaste_naam, vaste_telefoon FROM dienst_soorten WHERE vast = 1 AND actief = 1");
                    foreach ($vast as $v) {
                        $out[] = ['(vast)', $v['naam'], $v['vaste_naam'] ?: '—', $v['vaste_telefoon'] ?: '', ''];
                    }

                    return Resultaat::tabel(['Week', 'Dienst', 'Medewerker', 'Telefoon', 'Via'], $out, 'Effectief rooster (na ruilingen) uit de Spoedverhuur-app voor '.$p['periode']['label'].'.');
                }),

            new Vraag('spoed.persoon', self::BRON, 'Welke diensten heeft medewerker "X" dit jaar?',
                [['dienst', 'diensten', 'spoeddienst', 'storingsdienst', 'piket'], ['van', 'voor', 'heeft', 'draait']],
                ['medewerker', 'collega'], ['periode', 'naam'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    if (empty($p['naam'])) {
                        return Resultaat::tekst('Noem de naam, bijvoorbeeld: welke diensten heeft "Anne Vasseur" dit jaar?');
                    }
                    $rijen = $b->select(self::BRON, "SELECT w.jaar, w.weeknummer, w.van, ds.naam AS dienst, (t.dag_tm - t.dag_van + 1) AS dagen, t.oorsprong $f WHERE (m.naam LIKE ? OR t.rooster_naam LIKE ?) AND w.tm >= ? AND w.van <= ? ORDER BY w.van", ['%'.$p['naam'].'%', '%'.$p['naam'].'%', $p['periode']['van_s'], $p['periode']['tm_s']]);

                    return Resultaat::tabel(['Week', 'Van', 'Dienst', 'Dagen', 'Via'], array_map(fn ($x) => ['Week '.$x['weeknummer'].' '.$x['jaar'], $x['van'], $x['dienst'], (int) $x['dagen'], $x['oorsprong']], $rijen), 'Diensten van "'.$p['naam'].'" in '.$p['periode']['label'].' (effectief rooster).', ['Diensten' => count($rijen), 'Dagen' => array_sum(array_column($rijen, 'dagen'))]);
                }),

            new Vraag('spoed.belasting', self::BRON, 'Wie draait de meeste diensten (dagen per medewerker)?',
                [['dienst', 'diensten', 'spoeddienst', 'storingsdienst', 'piket'], ['meeste', 'hoeveel', 'per medewerker', 'belasting', 'verdeling', 'aantal', 'top', 'ranglijst']],
                [], ['periode', 'top'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) use ($f) {
                    $n = (int) ($p['top'] ?: 50);
                    $rijen = $b->select(self::BRON, "SELECT COALESCE(m.naam, t.rooster_naam) AS medewerker, COUNT(*) AS diensten, SUM(t.dag_tm - t.dag_van + 1) AS dagen, SUM(CASE WHEN ds.betaald = 1 THEN t.dag_tm - t.dag_van + 1 ELSE 0 END) AS betaalde_dagen $f WHERE w.tm >= ? AND w.van <= ? GROUP BY medewerker ORDER BY dagen DESC LIMIT $n", [$p['periode']['van_s'], $p['periode']['tm_s']]);

                    return Resultaat::tabel(['Medewerker', 'Diensten', 'Dagen', 'Betaalde dagen'], array_map(fn ($x) => [$x['medewerker'], (int) $x['diensten'], (int) $x['dagen'], (int) $x['betaalde_dagen']], $rijen), 'Aantal diensten en dagen per medewerker in '.$p['periode']['label'].' (effectief rooster, na ruilingen).');
                }),

            new Vraag('spoed.open', self::BRON, 'Welke diensten zijn nog niet ingevuld (open plekken)?',
                [['open plek', 'open plekken', 'niet ingevuld', 'onbemand', 'leeg', 'niemand', 'ontbreekt']],
                ['dienst', 'diensten', 'rooster'], ['periode'], 'iedereen', 'geen',
                function (DataBrug $b, array $p) {
                    $rijen = $b->select(self::BRON, "SELECT w.jaar, w.weeknummer, w.van, ds.naam AS dienst, t.rooster_naam FROM toewijzingen t JOIN rooster_weken w ON t.rooster_week_id = w.id JOIN dienst_soorten ds ON t.dienst_soort_id = ds.id WHERE t.medewerker_id IS NULL AND w.tm >= date('now') ORDER BY w.van, ds.volgorde LIMIT 100");
                    $ontbrekend = $b->select(self::BRON, "SELECT w.weeknummer, w.jaar, ds.naam AS dienst FROM rooster_weken w CROSS JOIN dienst_soorten ds WHERE ds.actief = 1 AND ds.vast = 0 AND w.tm >= date('now') AND w.van <= date('now','+56 day') AND NOT EXISTS (SELECT 1 FROM toewijzingen t WHERE t.rooster_week_id = w.id AND t.dienst_soort_id = ds.id) ORDER BY w.van");
                    $out = array_map(fn ($x) => ['Week '.$x['weeknummer'].' '.$x['jaar'], $x['dienst'], $x['rooster_naam'] ? 'naam niet gekoppeld: '.$x['rooster_naam'] : 'leeg'], $rijen);
                    foreach ($ontbrekend as $x) {
                        $out[] = ['Week '.$x['weeknummer'].' '.$x['jaar'], $x['dienst'], 'geen regel in rooster'];
                    }

                    return Resultaat::tabel(['Week', 'Dienst', 'Situatie'], $out, 'Toekomstige diensten zonder gekoppelde medewerker, plus ontbrekende regels (komende 8 weken).');
                }),

            new Vraag('spoed.ruilingen', self::BRON, 'Hoeveel ruilingen zijn er, en wie ruilt het vaakst?',
                [['ruil', 'ruilen', 'ruiling', 'ruilingen', 'geruild', 'overname', 'overgenomen']],
                ['dienst', 'diensten', 'vaakst', 'hoeveel'], ['periode', 'top'], 'iedereen', 'jaar',
                function (DataBrug $b, array $p) {
                    $st = $b->select(self::BRON, "SELECT status, COUNT(*) AS n FROM ruilingen WHERE created_at >= ? AND created_at <= ? GROUP BY status", [$p['periode']['van_s'], $p['periode']['tm_s'].' 23:59:59']);
                    $n = (int) ($p['top'] ?: 10);
                    $per = $b->select(self::BRON, "SELECT m.naam, SUM(CASE WHEN r.van_medewerker_id = m.id THEN 1 ELSE 0 END) AS weggegeven, SUM(CASE WHEN r.naar_medewerker_id = m.id THEN 1 ELSE 0 END) AS overgenomen FROM ruilingen r JOIN medewerkers m ON m.id IN (r.van_medewerker_id, r.naar_medewerker_id) WHERE r.status = 'bevestigd' AND r.created_at >= ? AND r.created_at <= ? GROUP BY m.naam ORDER BY weggegeven + overgenomen DESC LIMIT $n", [$p['periode']['van_s'], $p['periode']['tm_s'].' 23:59:59']);
                    $sub = [];
                    foreach ($st as $x) {
                        $sub[ucfirst($x['status'])] = (int) $x['n'];
                    }

                    return Resultaat::tabel(['Medewerker', 'Weggegeven', 'Overgenomen'], array_map(fn ($x) => [$x['naam'], (int) $x['weggegeven'], (int) $x['overgenomen']], $per), 'Bevestigde ruilingen in '.$p['periode']['label'].'; de kerngetallen tonen alle statussen.', $sub);
                }),

            new Vraag('spoed.kosten', self::BRON, 'Wat kosten de spoedverhuurdiensten per maand (vergoedingen)?',
                [['vergoeding', 'vergoedingen', 'uitroepvergoeding', 'kosten spoed', 'maandoverzicht', 'betaald aan', 'uitbetaald']],
                ['dienst', 'spoedverhuur', 'maand'], ['periode'], 'manager', 'jaar',
                function (DataBrug $b, array $p) {
                    $rijen = $b->select(self::BRON, "SELECT jaar, maand, aantal_regels, totaal_bedrag, verzonden_op, vergrendeld FROM maandoverzichten WHERE (jaar*100+maand) BETWEEN ? AND ? ORDER BY jaar, maand, id", [(int) substr($p['periode']['van_s'], 0, 4) * 100 + (int) substr($p['periode']['van_s'], 5, 2), (int) substr($p['periode']['tm_s'], 0, 4) * 100 + (int) substr($p['periode']['tm_s'], 5, 2)]);
                    $mn = ['', 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

                    return Resultaat::tabel(['Maand', 'Regels', 'Totaal (€)', 'Verzonden', 'Vergrendeld'], array_map(fn ($x) => [$mn[$x['maand']].' '.$x['jaar'], (int) $x['aantal_regels'], (float) $x['totaal_bedrag'], $x['verzonden_op'] ? substr($x['verzonden_op'], 0, 10) : '-', $x['vergrendeld'] ? 'ja' : 'nee'], $rijen), 'Aangemaakte maandoverzichten van uitroepvergoedingen in '.$p['periode']['label'].'.', ['Totaal' => '€ '.number_format(array_sum(array_column($rijen, 'totaal_bedrag')), 2, ',', '.')]);
                }),

            new Vraag('spoed.meldingen', self::BRON, 'Hoeveel Multiline-meldingen kwamen er binnen (per dienst, per uur)?',
                [['melding', 'meldingen', 'multiline', 'telefooncentrale', 'telefoontjes', 'gebeld', 'oproepen']],
                ['spoed', 'dienst', 'nacht', 'uur'], ['periode'], 'iedereen', 'maand',
                function (DataBrug $b, array $p) {
                    $per = $b->select(self::BRON, "SELECT COALESCE(ds.naam,'niet gekoppeld') AS dienst, COUNT(*) AS n FROM meldingen_multiline mm LEFT JOIN dienst_soorten ds ON mm.dienst_soort_id = ds.id WHERE mm.datum BETWEEN ? AND ? GROUP BY ds.naam ORDER BY n DESC", [$p['periode']['van_s'], $p['periode']['tm_s']]);
                    $uur = $b->select(self::BRON, "SELECT substr(mm.tijd,1,2) AS uur, COUNT(*) AS n FROM meldingen_multiline mm WHERE mm.datum BETWEEN ? AND ? AND mm.tijd IS NOT NULL GROUP BY uur ORDER BY uur", [$p['periode']['van_s'], $p['periode']['tm_s']]);
                    if (! $per) {
                        return Resultaat::tekst('Er zijn nog geen Multiline-meldingen geïmporteerd in de Spoedverhuur-app voor '.$p['periode']['label'].'. (De import van meldingen is fase 2 van die app.)');
                    }
                    $out = array_map(fn ($x) => ['Dienst: '.$x['dienst'], (int) $x['n']], $per);
                    foreach ($uur as $x) {
                        $out[] = ['Uur '.$x['uur'].':00', (int) $x['n']];
                    }

                    return Resultaat::tabel(['Groep', 'Meldingen'], $out, 'Meldingen van de telefooncentrale in '.$p['periode']['label'].', per dienstsoort en per uur van de dag.');
                }),
        ];
    }
}
