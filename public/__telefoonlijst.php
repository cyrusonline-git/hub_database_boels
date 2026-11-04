<?php
/**
 * Boels CORE — telefoonlijst/personeelsnummers AANVULLEN op medewerkerkaarten (blijft staan).
 *
 * Gebruik (CSV met ; en kop naam;personeelsnummer;telefoon, als upload-veld "csv"):
 *   proefdraaien: curl -F csv=@lijst.csv "https://databasehub.sorai.nl/__telefoonlijst.php?k=<DEPLOY_SECRET>"
 *   wegschrijven: curl -F csv=@lijst.csv "https://databasehub.sorai.nl/__telefoonlijst.php?k=<DEPLOY_SECRET>&apply=1"
 *
 * Regels — er wordt alleen AANGEVULD, nooit vervangen:
 *  - personeelsnummer: alleen gezet als het huidige nummer leeg is of automatisch
 *    gegenereerd ("AUTO-…"); een ander bestaand nummer blijft staan (CONFLICT in het rapport);
 *  - telefoon: alleen gezet als leeg; een ander bestaand nummer blijft staan (CONFLICT);
 *  - koppeling op naam (genormaliseerd: hoofdletters/accenten/volgorde onbelangrijk);
 *    niet of niet-eenduidig gevonden namen worden gemeld en overgeslagen;
 *  - met &maak=1: voor een naam ZONDER medewerkerkaart maar MET precies één
 *    gebruikersaccount (zelfde naam, nog niet gekoppeld) wordt een kaart aangemaakt
 *    (naam/e-mail van het account, depot/area/land uit allowed_*, optionele 4e
 *    CSV-kolom "functie") en aan het account gekoppeld (users.employee_id);
 *  - met &maak_nieuw=1: voor een naam zonder kaart én zonder account wordt een kaart
 *    aangemaakt met alleen naam, nummer, telefoon en de optionele kolommen
 *    functie;depot;area (kolom 4-6). Bedoeld voor collega's van een andere business
 *    unit (bijv. voor de Spoedverhuur-app). Deze kaarten krijgen source_system
 *    'telefoonlijst' en worden door de Industrial-import (sync-modus) met rust gelaten.
 * Wijzigingen lopen via het Employee-model (audit-log).
 */
$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) { $secret = trim($m[1]); break; }
}
if ($secret === null || $secret === '' || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(120);

$root = null;
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) { if (file_exists($p . '/vendor/autoload.php')) { $root = realpath($p); break; } }
if (! $root) exit("FOUT: Laravel niet gevonden.\n");
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// &verwijder=<id>: een per ongeluk (dubbel) aangemaakte kaart uit de telefoonlijst weer weghalen.
// Alleen kaarten met source_system 'telefoonlijst' zonder gekoppeld account; definitief.
if (($_GET['verwijder'] ?? '') !== '') {
    $id = (int) $_GET['verwijder'];
    $e = \App\Models\Employee::withTrashed()->find($id);
    if (! $e) exit("Kaart #$id niet gevonden.\n");
    if ($e->source_system !== 'telefoonlijst') exit("Kaart #$id ({$e->name}) komt niet uit de telefoonlijst (bron: " . ($e->source_system ?: '-') . ") — niet verwijderd.\n");
    if (\App\Models\User::withTrashed()->where('employee_id', $id)->exists()) exit("Kaart #$id ({$e->name}) heeft een gekoppeld account — niet verwijderd.\n");
    $naam = $e->name;
    $e->forceDelete();
    exit("Kaart #$id ($naam) definitief verwijderd.\n");
}
$apply = (($_GET['apply'] ?? '') === '1');
$maak  = (($_GET['maak'] ?? '') === '1');
$maakNieuw = (($_GET['maak_nieuw'] ?? '') === '1');
echo "Boels CORE — telefoonlijst aanvullen (" . ($apply ? 'WEGSCHRIJVEN' : 'PROEFDRAAIEN, niets wordt gewijzigd') . ")\n" . str_repeat('=', 70) . "\n\n";

if (empty($_FILES['csv']['tmp_name'])) {
    exit("Geen bestand ontvangen. Stuur een CSV (;) met kop naam;personeelsnummer;telefoon als upload-veld 'csv'.\n");
}
$fh = fopen($_FILES['csv']['tmp_name'], 'r');
$kop = fgetcsv($fh, 0, ';');
$rijen = [];
while (($r = fgetcsv($fh, 0, ';')) !== false) {
    if (count($r) < 2 || trim((string) $r[0]) === '') continue;
    $rijen[] = ['naam' => trim((string) $r[0]), 'nr' => trim((string) ($r[1] ?? '')), 'tel' => trim((string) ($r[2] ?? '')),
                'functie' => trim((string) ($r[3] ?? '')), 'depot' => trim((string) ($r[4] ?? '')), 'area' => trim((string) ($r[5] ?? ''))];
}
fclose($fh);
echo "Regels in lijst: " . count($rijen) . "\n\n";

$norm = function (string $s): array {
    $s = \Illuminate\Support\Str::ascii($s);
    $s = strtolower(preg_replace('/[^a-zA-Z ]+/', ' ', $s));
    return array_values(array_filter(explode(' ', $s)));
};
// Alleen cijfers, en een internationaal voorvoegsel 00… en +… gelijk behandelen (0032 = +32)
$cijfers = fn (?string $t) => preg_replace('/^00/', '', preg_replace('/\D+/', '', (string) $t));

$medewerkers = \App\Models\Employee::query()->orderBy('name')->get();
$opVolledig = []; $opSet = []; $opAchter = [];
foreach ($medewerkers as $e) {
    $t = $norm($e->name);
    if (! $t) continue;
    $opVolledig[implode(' ', $t)][] = $e;
    $s = $t; sort($s); $opSet[implode(' ', $s)][] = $e;
    $opAchter[end($t)][] = $e;
}
$nrInGebruik = $medewerkers->keyBy(fn ($e) => (string) $e->employee_number);

$stat = ['nr_gezet' => 0, 'tel_gezet' => 0, 'al_goed' => 0, 'conflict' => 0, 'niet_gevonden' => 0, 'meerdere' => 0, 'kaart_gemaakt' => 0];
$nietGevonden = []; $meerdere = []; $conflicten = []; $gemaakt = [];
// Gebruikersaccounts zonder medewerkerkaart, op genormaliseerde naam (voor &maak=1)
$accountsZonderKaart = [];
if ($maak) {
    foreach (\App\Models\User::whereNull('employee_id')->get() as $u) {
        $t = $norm($u->name); if ($t) $accountsZonderKaart[implode(' ', $t)][] = $u;
    }
}
foreach ($rijen as $rij) {
    $t = $norm($rij['naam']);
    $kand = $opVolledig[implode(' ', $t)] ?? [];
    if (! $kand) { $s = $t; sort($s); $kand = $opSet[implode(' ', $s)] ?? []; }
    if (! $kand && count($t) >= 2) {
        // achternaam gelijk + voornaam begint met dezelfde letter(s) (bijv. roepnaam vs. doopnaam)
        foreach ($opAchter[end($t)] ?? [] as $e) {
            $et = $norm($e->name);
            if ($et && $t && str_starts_with($et[0], substr($t[0], 0, 1))) $kand[] = $e;
        }
    }
    $kand = array_values(array_unique($kand, SORT_REGULAR));
    if (count($kand) === 0) {
        $accs = $accountsZonderKaart[implode(' ', $t)] ?? [];
        if ($maak && count($accs) === 1 && $rij['nr'] !== '') {
            $u = $accs[0];
            if ($nrInGebruik->has($rij['nr'])) {
                $conflicten[] = [$rij['naam'], "CONFLICT nr {$rij['nr']} is al in gebruik; geen kaart gemaakt"];
                printf("%-28s → account #%-4d %-26s CONFLICT nr al in gebruik\n", $rij['naam'], $u->id, mb_substr($u->name, 0, 26));
                continue;
            }
            $velden = [
                'employee_number' => $rij['nr'], 'name' => $u->name, 'email' => $u->email,
                'phone' => $rij['tel'] !== '' ? $rij['tel'] : null,
                'function' => $rij['functie'] !== '' ? $rij['functie'] : null,
                'depot' => $u->allowed_depots[0] ?? null, 'area' => $u->allowed_areas[0] ?? null, 'country' => $u->allowed_countries[0] ?? null,
                'active' => true, 'source_system' => 'telefoonlijst',
            ];
            if ($apply) {
                $e = \App\Models\Employee::create($velden);
                $u->employee_id = $e->id; $u->save();
            }
            $stat['kaart_gemaakt']++; $gemaakt[] = $rij['naam'];
            printf("%-28s → account #%-4d %-26s %s kaart: nr %s, tel %s, depot %s, area %s, functie %s\n", $rij['naam'], $u->id, mb_substr($u->name, 0, 26),
                $apply ? 'KAART AANGEMAAKT + gekoppeld' : 'zou kaart aanmaken + koppelen', $rij['nr'], $rij['tel'] ?: '-', $velden['depot'] ?: '-', $velden['area'] ?: '-', $velden['function'] ?: '-');
            continue;
        }
        if ($maakNieuw && count($accs) === 0 && $rij['nr'] !== '') {
            if ($nrInGebruik->has($rij['nr'])) {
                $conflicten[] = [$rij['naam'], "CONFLICT nr {$rij['nr']} is al in gebruik; geen kaart gemaakt"];
                printf("%-28s → %s\n", $rij['naam'], end($conflicten)[1]);
                continue;
            }
            $velden = [
                'employee_number' => $rij['nr'], 'name' => $rij['naam'], 'email' => null,
                'phone' => $rij['tel'] !== '' ? $rij['tel'] : null,
                'function' => $rij['functie'] !== '' ? $rij['functie'] : null,
                'depot' => $rij['depot'] !== '' ? $rij['depot'] : null, 'area' => $rij['area'] !== '' ? $rij['area'] : null,
                'active' => true, 'source_system' => 'telefoonlijst',
            ];
            if ($apply) { \App\Models\Employee::create($velden); }
            $stat['kaart_gemaakt']++; $gemaakt[] = $rij['naam'];
            printf("%-28s → %s nieuwe kaart (geen account): nr %s, tel %s, functie %s, depot %s, area %s\n", $rij['naam'],
                $apply ? 'KAART AANGEMAAKT' : 'zou kaart aanmaken', $rij['nr'], $rij['tel'] ?: '-', $velden['function'] ?: '-', $velden['depot'] ?: '-', $velden['area'] ?: '-');
            continue;
        }
        $stat['niet_gevonden']++; $nietGevonden[] = $rij;
        if (count($accs) > 0) { $nietGevonden[count($nietGevonden) - 1]['opm'] = count($accs) === 1 ? 'wel een gebruikersaccount (#' . $accs[0]->id . ') — gebruik &maak=1' : 'meerdere gebruikersaccounts'; }
        continue;
    }
    if (count($kand) > 1) { $stat['meerdere']++; $meerdere[] = [$rij, array_map(fn ($e) => "#{$e->id} {$e->name}", $kand)]; continue; }
    $e = $kand[0];
    $acties = [];
    // personeelsnummer
    $huidigNr = (string) $e->employee_number;
    if ($rij['nr'] !== '') {
        if ($huidigNr === $rij['nr']) {
            $acties[] = "nr ok ({$huidigNr})";
        } elseif ($huidigNr === '' || str_starts_with($huidigNr, 'AUTO-')) {
            $ander = $nrInGebruik->get($rij['nr']);
            if ($ander && $ander->id !== $e->id) {
                $acties[] = "CONFLICT nr {$rij['nr']} is al van #{$ander->id} {$ander->name}"; $conflicten[] = [$rij['naam'], end($acties)];
            } else {
                $acties[] = "nr {$huidigNr} → {$rij['nr']}"; $stat['nr_gezet']++;
                if ($apply) { $e->employee_number = $rij['nr']; }
            }
        } else {
            $acties[] = "CONFLICT nr: bestaand {$huidigNr}, lijst {$rij['nr']} (niet gewijzigd)"; $conflicten[] = [$rij['naam'], end($acties)];
        }
    }
    // telefoon
    $huidigTel = trim((string) $e->phone);
    if ($rij['tel'] !== '') {
        if ($huidigTel === '') {
            $acties[] = "tel → {$rij['tel']}"; $stat['tel_gezet']++;
            if ($apply) { $e->phone = $rij['tel']; }
        } elseif ($cijfers($huidigTel) === $cijfers($rij['tel'])) {
            $acties[] = "tel ok";
        } else {
            $acties[] = "CONFLICT tel: bestaand {$huidigTel}, lijst {$rij['tel']} (niet gewijzigd)"; $conflicten[] = [$rij['naam'], end($acties)];
        }
    }
    if ($apply && $e->isDirty()) { $e->save(); }
    if ($acties && ! preg_grep('/→|CONFLICT/u', $acties)) { $stat['al_goed']++; }
    printf("%-28s → #%-4d %-30s %s\n", $rij['naam'], $e->id, mb_substr($e->name, 0, 30), implode(' | ', $acties));
}
$stat['conflict'] = count($conflicten);

echo "\n" . str_repeat('-', 70) . "\nSAMENVATTING" . ($apply ? ' (weggeschreven)' : ' (proef)') . "\n";
echo "  personeelsnummer " . ($apply ? 'gezet' : 'te zetten') . ": {$stat['nr_gezet']}\n";
echo "  telefoon " . ($apply ? 'gezet' : 'te zetten') . ":         {$stat['tel_gezet']}\n";
echo "  al volledig goed:         {$stat['al_goed']}\n";
echo "  conflicten (ongewijzigd): {$stat['conflict']}\n";
echo "  niet gevonden:            {$stat['niet_gevonden']}\n";
echo "  meerdere kandidaten:      {$stat['meerdere']}\n";
if ($maak || $maakNieuw) echo "  kaart " . ($apply ? 'aangemaakt' : 'aan te maken') . ": {$stat['kaart_gemaakt']}" . ($gemaakt ? ' (' . implode(', ', $gemaakt) . ')' : '') . "\n";
if ($conflicten) { echo "\nCONFLICTEN (handmatig beoordelen):\n"; foreach ($conflicten as [$n, $a]) echo "  - $n: $a\n"; }
if ($meerdere) { echo "\nMEERDERE KANDIDATEN (overgeslagen):\n"; foreach ($meerdere as [$rij, $k]) echo "  - {$rij['naam']} ({$rij['nr']}): " . implode(' / ', $k) . "\n"; }
if ($nietGevonden) { echo "\nNIET GEVONDEN in de medewerkerkaarten (overgeslagen):\n"; foreach ($nietGevonden as $rij) echo "  - {$rij['naam']} ({$rij['nr']}, {$rij['tel']})" . (isset($rij['opm']) ? " — {$rij['opm']}" : '') . "\n"; }
echo "\n" . ($apply ? "✓ Klaar." : "Proef klaar — voeg &apply=1 toe om weg te schrijven.") . "\n";
