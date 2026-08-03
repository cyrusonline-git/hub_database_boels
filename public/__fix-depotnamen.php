<?php
/**
 * Boels CORE — Eenmalig: depotnamen opschonen (alles vóór de ";").
 * Werkt bij: employees.depot, users.allowed_depots, org_depots.name,
 * applications.restricted_to_depots. Verwijdert zichzelf na succes.
 * Sleutel = DEPLOY_SECRET.
 */

$envFile = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $envFile = realpath($p); break; }
}
if (! $envFile) exit('env niet gevonden');
$c = file_get_contents($envFile);
if (! preg_match('/^DEPLOY_SECRET=(.+)$/m', $c, $m)
    || ! hash_equals(trim($m[1]), (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        break;
    }
}

use Illuminate\Support\Facades\DB;

function schoon(?string $naam): ?string {
    if ($naam === null) return null;
    $s = trim(explode(';', $naam)[0]);
    return $s === '' ? null : $s;
}

echo "Boels CORE — depotnamen opschonen\n" . str_repeat('=', 50) . "\n\n";

// 1. Medewerkers
$n = 0;
foreach (DB::table('employees')->whereNotNull('depot')->get(['id', 'depot']) as $e) {
    $nieuw = schoon($e->depot);
    if ($nieuw !== $e->depot) {
        DB::table('employees')->where('id', $e->id)->update(['depot' => $nieuw]);
        $n++;
    }
}
echo "Medewerkers bijgewerkt: $n\n";

// 2. Gebruikers (allowed_depots JSON)
$n = 0;
foreach (DB::table('users')->whereNotNull('allowed_depots')->get(['id', 'allowed_depots']) as $u) {
    $lijst = json_decode($u->allowed_depots, true);
    if (! is_array($lijst)) continue;
    $nieuw = array_values(array_unique(array_filter(array_map('schoon', $lijst))));
    if ($nieuw !== $lijst) {
        DB::table('users')->where('id', $u->id)->update(['allowed_depots' => json_encode($nieuw)]);
        $n++;
    }
}
echo "Gebruikers (toegangsdepots) bijgewerkt: $n\n";

// 3. Infrastructuur (org_depots) — met samenvoegen bij dubbele namen
$n = 0;
foreach (DB::table('org_depots')->get() as $d) {
    $nieuw = schoon($d->name);
    if ($nieuw === $d->name || $nieuw === null) continue;
    $bestaat = DB::table('org_depots')->where('area_id', $d->area_id)
        ->where('name', $nieuw)->where('id', '!=', $d->id)->exists();
    if ($bestaat) {
        DB::table('org_depots')->where('id', $d->id)->delete(); // dubbele → weg
    } else {
        DB::table('org_depots')->where('id', $d->id)->update(['name' => $nieuw]);
    }
    $n++;
}
echo "Infrastructuur-depots bijgewerkt: $n\n";

// 4. App-restricties
$n = 0;
foreach (DB::table('applications')->whereNotNull('restricted_to_depots')->get(['id', 'restricted_to_depots']) as $a) {
    $lijst = json_decode($a->restricted_to_depots, true);
    if (! is_array($lijst)) continue;
    $nieuw = array_values(array_unique(array_filter(array_map('schoon', $lijst))));
    if ($nieuw !== $lijst) {
        DB::table('applications')->where('id', $a->id)->update(['restricted_to_depots' => json_encode($nieuw)]);
        $n++;
    }
}
echo "App-restricties bijgewerkt: $n\n";

echo "\nDepotnamen nu (medewerkers):\n";
foreach (DB::table('employees')->whereNotNull('depot')->selectRaw('depot, count(*) c')->groupBy('depot')->orderBy('depot')->get() as $r) {
    printf("  %-35s (%dx)\n", $r->depot, $r->c);
}

@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf verwijderd.\n";
