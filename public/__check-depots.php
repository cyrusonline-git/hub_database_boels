<?php
/**
 * Boels CORE — Diagnose depotnamen (alleen-lezen).
 * Sleutel = DEPLOY_SECRET. Verwijdert zichzelf na gebruik.
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

echo "Unieke depot-waarden op MEDEWERKERS (waarde | lengte | aantal medewerkers):\n";
$rows = DB::table('employees')->whereNull('deleted_at')->whereNotNull('depot')
    ->selectRaw('depot, count(*) as c')->groupBy('depot')->orderBy('depot')->get();
foreach ($rows as $r) {
    printf("  [%s]  (len %d, %dx)\n", $r->depot, mb_strlen($r->depot), $r->c);
}

echo "\nDepots in INFRASTRUCTUUR (org_depots):\n";
foreach (DB::table('org_depots')->whereNull('deleted_at')->orderBy('name')->get() as $d) {
    printf("  [%s]  (len %d)\n", $d->name, mb_strlen($d->name));
}

@unlink(__FILE__);
echo "\nKlaar — script heeft zichzelf verwijderd.\n";
