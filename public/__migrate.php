<?php
/**
 * Boels CORE — Migrate-only script.
 *
 * Wordt via geheime URL aangeroepen om alleen pending migrations te draaien.
 * (APP_KEY is al gezet bij eerste __setup.php run.)
 *
 * Verwijdert zichzelf na succes.
 *
 * Open: https://databasehub.sorai.nl/__migrate.php?k=BOELS_MIGRATE_2026
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

// Lokaliseer Laravel
$candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
$root = null;
foreach ($candidates as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        $root = realpath($p);
        break;
    }
}
if (! $root) {
    exit("FOUT: Laravel installatie niet gevonden.\n");
}
echo "Laravel root: $root\n\n";

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$buffer = new \Symfony\Component\Console\Output\BufferedOutput;

try {
    echo "[1/2] migrate --force\n";
    $exit = $kernel->call('migrate', ['--force' => true], $buffer);
    echo $buffer->fetch();
    echo "exit code: $exit\n\n";

    echo "[2/2] cache clearen (config + view + route)\n";
    $kernel->call('config:clear', [], $buffer);
    echo $buffer->fetch();
    $kernel->call('view:clear', [], $buffer);
    echo $buffer->fetch();
    $kernel->call('route:clear', [], $buffer);
    echo $buffer->fetch();

    if ($exit === 0) {
        echo "\n✓ Migrate geslaagd.\n";
        @unlink(__FILE__);
        echo "Dit script heeft zichzelf verwijderd.\n";
    } else {
        echo "\n⚠ Migrate gefaald — script NIET verwijderd zodat je opnieuw kunt proberen.\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}
