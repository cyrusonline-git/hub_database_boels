<?php
/**
 * Boels CORE — Eenmalig .env patch script.
 *
 * Voegt SESSION_DOMAIN / SESSION_SECURE_COOKIE / SESSION_SAME_SITE /
 * SANCTUM_STATEFUL_DOMAINS toe aan de productie .env als ze ontbreken.
 *
 * Open: https://databasehub.sorai.nl/__env-patch.php?k=BOELS_ENV_2026
 * Verwijdert zichzelf na succes.
 */

$secret = 'BOELS_ENV_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Boels CORE — .env patch voor SSO\n";
echo str_repeat('=', 50) . "\n\n";

// Lokaliseer .env in laravel_app
$candidates = [__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'];
$envFile = null;
foreach ($candidates as $p) {
    if (file_exists($p)) {
        $envFile = realpath($p);
        break;
    }
}
if (! $envFile) {
    exit("FOUT: .env bestand niet gevonden in laravel_app/ of root.\n");
}
echo "Gevonden: $envFile\n\n";

$contents = file_get_contents($envFile);

// Patches: key => value
$patches = [
    'SESSION_DOMAIN' => '.sorai.nl',
    'SESSION_SECURE_COOKIE' => 'true',
    'SESSION_SAME_SITE' => 'lax',
    'SANCTUM_STATEFUL_DOMAINS' => 'databasehub.sorai.nl,hub.sorai.nl,fleet.sorai.nl,sales.sorai.nl,schade.sorai.nl,ai.sorai.nl,werkbon.sorai.nl,monteurs.sorai.nl,rapportage.sorai.nl',
];

$added = 0;
$alreadyPresent = 0;

// Voeg een sectie-header toe als de SSO-vars nog niet bestaan
if (! preg_match('/^SESSION_DOMAIN=/m', $contents)
    && ! preg_match('/^SANCTUM_STATEFUL_DOMAINS=/m', $contents)) {
    $contents = rtrim($contents) . "\n\n# SSO over alle *.sorai.nl subdomeinen (auto-patched)\n";
}

foreach ($patches as $key => $value) {
    if (preg_match('/^' . preg_quote($key, '/') . '=/m', $contents)) {
        echo "  [SKIP] $key staat al in .env\n";
        $alreadyPresent++;
        continue;
    }
    $contents .= "$key=$value\n";
    echo "  [ADD]  $key=$value\n";
    $added++;
}

if ($added === 0) {
    echo "\nAlle vars stonden al in .env. Niets gewijzigd.\n";
} else {
    if (file_put_contents($envFile, $contents) === false) {
        exit("\nFOUT: kan .env niet schrijven (check permissies)\n");
    }
    echo "\n✓ $added var(s) toegevoegd. ($alreadyPresent waren er al.)\n";

    // Config-cache clearen zodat Laravel de nieuwe waardes oppikt
    $candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
    foreach ($candidates as $p) {
        if (file_exists($p . '/vendor/autoload.php')) {
            require $p . '/vendor/autoload.php';
            $app = require_once $p . '/bootstrap/app.php';
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $buf = new \Symfony\Component\Console\Output\BufferedOutput;
            $kernel->call('config:clear', [], $buf);
            $kernel->call('cache:clear', [], $buf);
            echo "✓ Config + cache gecleared.\n";
            break;
        }
    }
}

@unlink(__FILE__);
echo "\nDit script heeft zichzelf verwijderd.\n";
echo "\nVergeet niet je sessie opnieuw te starten — log uit en weer in,\n";
echo "anders draait je huidige sessie nog op de oude cookie.\n";
