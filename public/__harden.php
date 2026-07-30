<?php
/**
 * Boels CORE — Eenmalig hardening-script.
 *
 * 1. Zet APP_DEBUG=false en APP_ENV=production (geen stacktraces/SQL naar bezoekers)
 * 2. Genereert een geheime DEPLOY_SECRET in .env — daarna werken
 *    __pull_deploy.php en __migrate.php alleen nog met die sleutel
 *    (de oude vaste sleutels uit de publieke repo vervallen).
 * 3. Cleart de config-cache.
 *
 * Open: https://databasehub.sorai.nl/__harden.php?k=BOELS_HARDEN_2026
 * Verwijdert zichzelf na succes. BEWAAR DE GETOONDE DEPLOY-URL!
 */

$secret = 'BOELS_HARDEN_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
echo "Boels CORE — Hardening\n" . str_repeat('=', 50) . "\n\n";

$envFile = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $envFile = realpath($p); break; }
}
if (! $envFile) exit("FOUT: .env niet gevonden.\n");

$contents = file_get_contents($envFile);

$set = function (string $key, string $value) use (&$contents) {
    if (preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $contents, $m)) {
        $oud = trim($m[1]);
        if ($oud === $value) { echo "  [OK]  $key stond al op $value\n"; return; }
        $contents = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', "$key=$value", $contents);
        echo "  [SET] $key: $oud → $value\n";
    } else {
        $contents = rtrim($contents) . "\n$key=$value\n";
        echo "  [ADD] $key=$value\n";
    }
};

echo "Debug uitzetten:\n";
$set('APP_DEBUG', 'false');
$set('APP_ENV', 'production');

echo "\nDeploy-sleutel:\n";
if (preg_match('/^DEPLOY_SECRET=(.+)$/m', $contents, $m)) {
    $deploySecret = trim($m[1]);
    echo "  [OK]  DEPLOY_SECRET bestond al.\n";
} else {
    $deploySecret = bin2hex(random_bytes(20));
    $set('DEPLOY_SECRET', $deploySecret);
}

file_put_contents($envFile, $contents);
echo "\n.env opgeslagen.\n";

// Config cache clearen
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        $buf = new \Symfony\Component\Console\Output\BufferedOutput;
        $kernel->call('config:clear', [], $buf);
        echo "Config cache gecleared.\n";
        break;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "BEWAAR DEZE URLS (de oude sleutels werken niet meer):\n\n";
echo "Deploy:  https://databasehub.sorai.nl/__pull_deploy.php?k=$deploySecret\n";
echo "Migrate: https://databasehub.sorai.nl/__migrate.php?k=$deploySecret\n";
echo str_repeat('=', 50) . "\n";

@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf verwijderd.\n";
