<?php
/**
 * Boels CORE — Diagnose klantenzoek (alleen-lezen). Verwijdert zichzelf.
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

echo "Totaal klanten: " . DB::table('customers')->whereNull('deleted_at')->count() . "\n\n";
foreach (['BAM', 'DAMEN', 'Damen', 'Shell'] as $q) {
    $hits = DB::table('customers')->whereNull('deleted_at')
        ->where(function ($w) use ($q) {
            $w->where('customer_number', 'like', "%$q%")
              ->orWhere('customer_name', 'like', "%$q%")
              ->orWhere('concern_name', 'like', "%$q%");
        })->limit(5)->get(['customer_number', 'customer_name', 'concern_name']);
    echo "Zoekterm \"$q\": " . $hits->count() . " treffer(s)\n";
    foreach ($hits as $h) {
        echo "  #{$h->customer_number}  {$h->customer_name}  (concern: {$h->concern_name})\n";
    }
}

@unlink(__FILE__);
echo "\nKlaar — script heeft zichzelf verwijderd.\n";
