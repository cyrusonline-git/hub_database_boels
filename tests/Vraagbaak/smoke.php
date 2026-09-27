<?php
// Draaien: php tests/Vraagbaak/smoke.php  (maakt fixture-databases in de tmp-map; geen CORE-database nodig)// Rooktest Vraagbaak zonder CORE-database: parser + bronnen op fixture-databases
putenv('VRAAGBAAK_DOMAINS_DIR=' . sys_get_temp_dir() . '/vraagbaak-fixtures/domains'); putenv('CACHE_STORE=array'); putenv('SESSION_DRIVER=array'); putenv('DB_CONNECTION=sqlite'); putenv('DB_DATABASE=:memory:'); putenv('APP_ENV=testing');
$_ENV['VRAAGBAAK_DOMAINS_DIR'] = sys_get_temp_dir() . '/vraagbaak-fixtures/domains';
$core = dirname(__DIR__, 2); require __DIR__ . '/fixtures.php';
require $core . '/vendor/autoload.php';
$app = require $core . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['vraagbaak.domains_dir' => sys_get_temp_dir() . '/vraagbaak-fixtures/domains', 'cache.default' => 'array']);
\Illuminate\Support\Facades\Cache::put('vraagbaak:depots', [
    ['naam' => 'Rotterdam-Europoort', 'plaats' => 'Rotterdam', 'area' => 'West', 'nummers' => ['759'], 'termen' => ['rotterdam', 'europoort']],
    ['naam' => 'Geleen - Chemelot', 'plaats' => 'Geleen', 'area' => 'Zuid', 'nummers' => ['384', '769'], 'termen' => ['geleen', 'chemelot']],
    ['naam' => 'Shell Pernis', 'plaats' => 'Rotterdam', 'area' => 'Klantlocaties', 'nummers' => [], 'termen' => ['shell', 'pernis']],
], 3600);
use App\Vraagbaak\{Catalogus, Herkenner, Periode, Depots, DataBrug};
$fouten = 0; $ok = 0;
function check($cond, $msg) { global $fouten, $ok; if ($cond) { $ok++; } else { $fouten++; echo "  FOUT: $msg\n"; } }
$alle = array_values(Catalogus::alle());
echo "Catalogus: " . count($alle) . " vragen\n";
// Periode
$p = Periode::herken('hoeveel liter deze maand'); check($p && $p['label'] === Periode::maandNaam((int) date('n')) . ' ' . date('Y'), 'deze maand');
$p = Periode::herken('vorige maand'); check($p && $p['van_s'] === date('Y-m-01', strtotime('first day of last month')), 'vorige maand');
$p = Periode::herken('in week 40 van 2025'); check($p && $p['van_s'] === '2025-09-29', 'week 40 2025: ' . ($p['van_s'] ?? '-'));
$p = Periode::herken('schades in september'); check($p && substr($p['van_s'], 5, 2) === '09', 'september');
$p = Periode::herken('dit jaar'); check($p && $p['van_s'] === date('Y') . '-01-01', 'dit jaar');
$p = Periode::herken('afgelopen 30 dagen'); check($p && $p['label'] === 'afgelopen 30 dagen', 'afgelopen 30 dagen');
$p = Periode::herken('van 1-9 tot 15-9-2026'); check($p && $p['van_s'] === '2026-09-01' && $p['tm_s'] === '2026-09-15', 'datumbereik');
$p = Periode::herken('in 2025'); check($p && $p['van_s'] === '2025-01-01', '2025');
// Depots
$d = Depots::herken('hoeveel liter heeft rdam getankt'); check($d && $d['naam'] === 'Rotterdam-Europoort', 'rdam → Rotterdam-Europoort: ' . json_encode($d));
$d = Depots::herken('schades chemelot'); check($d && $d['naam'] === 'Geleen - Chemelot', 'chemelot');
$d = Depots::herken('inhuur in area zuid'); check($d && $d['type'] === 'area' && $d['naam'] === 'Zuid', 'area zuid');
$d = Depots::herken('wat staat er bij pernis'); check($d && $d['naam'] === 'Shell Pernis', 'pernis');
check(Depots::herken('hoeveel liter getankt') === null, 'geen depot');
// Herkenner
$tests = [
    'hoeveel liter heeft rdam afgetankt deze maand' => 'tank.liters',
    'Hoeveel liter is er getankt in Rotterdam?' => 'tank.liters',
    'top 10 machines op brandstof dit jaar' => 'tank.top_machines',
    'welke vestigingen tanken het meest' => 'tank.top_depots',
    'welke inhuur is te laat retour' => 'inhuur.te_laat',
    'wat loopt er nu in huur bij chemelot' => 'inhuur.lopend',
    'top 5 leveranciers dit jaar' => 'inhuur.leveranciers',
    'hoeveel schades vorige maand in area zuid' => 'schade.aantal',
    'welke schades staan nog open' => 'schade.open',
    'wie heeft volgende week dienst' => 'spoed.dienst',
    'wie draait de meeste diensten dit jaar' => 'spoed.belasting',
    'hoeveel ruilingen zijn er geweest' => 'spoed.ruilingen',
    'hoeveel materieel is beschikbaar in rotterdam' => 'voorraad.beschikbaar',
    'waar zitten we onder de minimumvoorraad' => 'voorraad.minimum',
    'hoeveel items staan er nog op locatie' => 'scan.op_locatie',
    'waar is machine 7590019510' => 'scan.machine',
    'depot scorekaart deze maand' => 'combi.scorekaart',
    'welke machines hebben getankt en een schade' => 'combi.tank_schade',
];
foreach ($tests as $vraag => $verwacht) {
    $h = Herkenner::herken($vraag, $alle);
    $id = $h['vraag']?->id;
    check($h['status'] === 'ok' && $id === $verwacht, "'$vraag' → " . ($h['status'] === 'ok' ? $id : $h['status'] . ' ' . json_encode(array_column($h['kandidaten'], 'id'))) . " (verwacht $verwacht)");
}
$h = Herkenner::herken('wat is het weer morgen', $alle); check($h['status'] === 'onbekend', 'onbekende vraag → onbekend (' . $h['status'] . ')');
// Berekeningen op fixtures
$b = new DataBrug();
$diag = $b->diagnose(); foreach ($diag as $r) { if ($r['bron'] !== 'shell') check($r['leesbaar'], 'bron leesbaar: ' . $r['bron'] . ' ' . ($r['fout'] ?? '')); }
$h = Herkenner::herken('hoeveel liter heeft rdam getankt deze maand', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); }
check(abs($res->waarde - 200.5) < 0.01, 'tank rdam deze maand = 200.5, was ' . $res->waarde); check(($res->sub['Tankbeurten'] ?? 0) === 2, 'tankbeurten 2');
$h = Herkenner::herken('hoeveel liter getankt deze maand', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); }
check(abs($res->waarde - 400.5) < 0.01, 'tank alle depots (zonder BLS) = 400.5, was ' . $res->waarde);
$h = Herkenner::herken('welke inhuur is te laat', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check(count($res->rijen) === 1 && $res->rijen[0][2] === 'Hoogwerker 12m', 'inhuur te laat: 1 rij');
$h = Herkenner::herken('hoeveel schades deze maand in rotterdam', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check($res->waarde === 2 && $res->sub['Doorbelast'] === '€ 450,00', 'schades rdam: 2, doorbelast 450: ' . json_encode($res->sub));
$h = Herkenner::herken('wie heeft dienst deze week', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check($res->type === 'tabel', 'spoed dienst tabel (' . count($res->rijen) . ' rijen)');
$h = Herkenner::herken('hoeveel materieel is beschikbaar', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check(($res->sub['Beschikbaar'] ?? 0) === 2, 'voorraad beschikbaar 2: ' . json_encode($res->sub));
$h = Herkenner::herken('waar zitten we onder de minimumvoorraad', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check(count($res->rijen) === 1 && $res->rijen[0][5] === 2, 'min voorraad tekort 2');
$h = Herkenner::herken('hoeveel items staan er nog op locatie', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check(($res->sub['Totaal items'] ?? 0) === 2, 'scanner op locatie 2');
$h = Herkenner::herken('welke machines hebben getankt en een schade deze maand', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check(count($res->rijen) === 1 && $res->rijen[0][0] === '7590019510', 'combi tank+schade: 7590019510');
$h = Herkenner::herken('depot scorekaart deze maand', $alle); $res = $h["vraag"] ? $h["vraag"]->bereken($b, $h["params"]) : new App\Vraagbaak\Resultaat("tekst", "GEEN", "", [], [], "", []); if (!$h["vraag"]) { check(false, "geen vraag herkend: " . $h["status"] . " " . json_encode(array_column($h["kandidaten"], "id"))); } check(count($res->rijen) === 3 && $res->rijen[0][1] == 200.5, 'scorekaart 3 depots, rdam 200.5 l: ' . json_encode($res->rijen[0]));
echo "\nOK: $ok  FOUT: $fouten\n";
exit($fouten ? 1 : 0);
