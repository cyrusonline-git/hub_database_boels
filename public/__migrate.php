<?php
/**
 * Boels CORE — Migrate-only script.
 *
 * Open: https://databasehub.sorai.nl/__migrate.php?k=BOELS_MIGRATE_2026
 * Verwijdert zichzelf na succes.
 */

$secret = 'BOELS_MIGRATE_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

@set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

echo "Boels CORE — Migrate\n";
echo str_repeat('=', 50) . "\n\n";

$candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
$root = null;
foreach ($candidates as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        $root = realpath($p);
        break;
    }
}
if (! $root) exit("FOUT: Laravel niet gevonden.\n");
echo "Laravel root: $root\n\n";

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$buf = new \Symfony\Component\Console\Output\BufferedOutput;

try {
    echo "[1/2] migrate --force\n";
    $exit = $kernel->call('migrate', ['--force' => true], $buf);
    echo $buf->fetch();
    echo "exit code: $exit\n\n";

    echo "[2/2] cache clearen\n";
    $kernel->call('config:clear', [], $buf);   echo $buf->fetch();
    $kernel->call('view:clear', [], $buf);     echo $buf->fetch();
    $kernel->call('route:clear', [], $buf);    echo $buf->fetch();
    $kernel->call('cache:clear', [], $buf);    echo $buf->fetch();

    if ($exit === 0) {
        echo "\n✓ Migrate geslaagd.\n";
        @unlink(__FILE__);
        echo "Dit script heeft zichzelf verwijderd.\n";
    } else {
        echo "\n⚠ Migrate gefaald — script blijft staan.\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}
