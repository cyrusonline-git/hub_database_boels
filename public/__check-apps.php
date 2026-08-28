<?php
/**
 * Eenmalige controle: tegel-URL's van rmw en bp tonen. Verwijdert zichzelf.
 */
$envFile = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $envFile = realpath($p); break; }
}
if (! $envFile) exit('env niet gevonden');
$contents = file_get_contents($envFile);
if (! preg_match('/^DEPLOY_SECRET=(.+)$/m', $contents, $m)
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
        foreach (\Illuminate\Support\Facades\DB::table('applications')
            ->whereIn('slug', ['rmw', 'bp'])->get(['id', 'slug', 'name', 'url', 'active', 'color']) as $row) {
            echo json_encode($row) . "\n";
        }
        break;
    }
}
@unlink(__FILE__);
echo "✓ (script heeft zichzelf verwijderd)\n";
