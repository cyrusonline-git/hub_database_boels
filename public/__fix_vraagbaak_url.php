<?php
/**
 * Boels CORE — eenmalig herstel: zet de URL van de Vraagbaak-tegel terug.
 *
 * De link van de AI data bot (slug 'ai' = "Boels Vraagbaak") was per ongeluk
 * leeggemaakt in de applicaties-instellingen. Dit endpoint zet url =
 * '/tools/vraagbaak' terug, maakt de tegel weer actief en haalt een eventuele
 * soft-delete weg. Idempotent — meermaals draaien mag.
 *
 * Open: https://databasehub.sorai.nl/__fix_vraagbaak_url.php?k=<DEPLOY_SECRET>
 */

$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) {
        $secret = trim($m[1]);
        break;
    }
}
if ($secret === null || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$root = null;
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        $root = realpath($p);
        break;
    }
}
if (! $root) exit("FOUT: Laravel niet gevonden.\n");

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rij = DB::table('applications')->where('slug', 'ai')->first();
if (! $rij) {
    exit("GEEN tegel met slug 'ai' gevonden. Niets gewijzigd.\n");
}

echo "Voor:\n  naam=" . $rij->name . "\n  url=" . var_export($rij->url, true)
    . "\n  active=" . var_export($rij->active, true)
    . "\n  deleted_at=" . var_export($rij->deleted_at, true) . "\n\n";

DB::table('applications')->where('slug', 'ai')->update([
    'url'        => '/tools/vraagbaak',
    'active'     => 1,
    'deleted_at' => null,
    'updated_at' => now(),
]);

$na = DB::table('applications')->where('slug', 'ai')->first();
echo "Na:\n  naam=" . $na->name . "\n  url=" . var_export($na->url, true)
    . "\n  active=" . var_export($na->active, true)
    . "\n  deleted_at=" . var_export($na->deleted_at, true) . "\n\n";

echo "KLAAR — de Vraagbaak-tegel wijst weer naar /tools/vraagbaak.\n";
echo "Verwijder dit bestand daarna (of open met ?weg=1).\n";

if (($_GET['weg'] ?? '') === '1') { @unlink(__FILE__); echo "(herstel-bestand verwijderd)\n"; }
