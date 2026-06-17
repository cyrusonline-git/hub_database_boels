<?php
/**
 * Boels CORE — Migrate + Employee aliases seeder.
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

echo "Boels CORE — Migrate + Employee aliases\n";
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
    echo "[1/3] migrate --force\n";
    $exit = $kernel->call('migrate', ['--force' => true], $buf);
    echo $buf->fetch();
    echo "exit code: $exit\n\n";

    echo "[2/3] EmployeeFieldAliasSeeder\n";
    $kernel->call('db:seed', ['--class' => 'Database\\Seeders\\EmployeeFieldAliasSeeder', '--force' => true], $buf);
    echo $buf->fetch();
    echo "\n";

    echo "[3/3] cache clearen\n";
    $kernel->call('config:clear', [], $buf);  echo $buf->fetch();
    $kernel->call('view:clear', [], $buf);    echo $buf->fetch();
    $kernel->call('route:clear', [], $buf);   echo $buf->fetch();
    $kernel->call('cache:clear', [], $buf);   echo $buf->fetch();

    if ($exit === 0) {
        echo "\n✓ Klaar. Employees tabel uitgebreid met area/country/city/region/manager/etc.\n";
        echo "  Field aliases voor NL+EN kolomkoppen toegevoegd.\n";
        @unlink(__FILE__);
        echo "Script verwijderd.\n";
    } else {
        echo "\n⚠ Migrate gefaald — script blijft staan.\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}
