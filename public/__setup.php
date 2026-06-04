<?php
/**
 * Boels CORE — eenmalig setup-script.
 *
 * Wordt via een geheime URL aangeroepen om:
 *   1. APP_KEY te genereren in .env
 *   2. Database migraties te draaien
 *   3. Seeders te draaien (Super Admin + apps + field aliases)
 *
 * Verwijdert zichzelf na succes zodat het niet opnieuw kan draaien.
 *
 * Open: https://databasehub.sorai.nl/__setup.php?k=BOELS_SETUP_2026
 */

$secret = 'BOELS_SETUP_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

@set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

echo "Boels CORE — eenmalige setup\n";
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

// ============ STAP 1: APP_KEY in .env ============
echo "[1/3] APP_KEY genereren in .env\n";

$envFile = $root . '/.env';
if (! file_exists($envFile)) {
    exit("FOUT: .env bestand ontbreekt op $envFile\n");
}

$env = file_get_contents($envFile);
preg_match('/^APP_KEY=(.*)$/m', $env, $m);

if (! empty($m[1])) {
    echo "      → APP_KEY was al gezet. Overslaan.\n";
} else {
    $key = 'base64:' . base64_encode(random_bytes(32));
    if (preg_match('/^APP_KEY=.*$/m', $env)) {
        $env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $env);
    } else {
        $env .= "\nAPP_KEY=" . $key . "\n";
    }
    if (file_put_contents($envFile, $env) === false) {
        exit("FOUT: kan .env niet schrijven (controleer permissies)\n");
    }
    echo "      → APP_KEY gegenereerd en opgeslagen.\n";
}

// ============ STAP 2: Bootstrap Laravel ============
echo "\n[2/3] Laravel bootstrappen\n";

require $root . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $root . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "      → OK\n";

// ============ STAP 3: Migrate + Seed ============
echo "\n[3/3] Database migrate --seed\n";
echo "      Dit kan 10-30 seconden duren...\n\n";

$buffer = new \Symfony\Component\Console\Output\BufferedOutput;

try {
    $exitMigrate = $kernel->call('migrate', ['--force' => true], $buffer);
    echo "----- migrate output -----\n";
    echo $buffer->fetch();
    echo "----- exit code: $exitMigrate -----\n\n";

    $exitSeed = $kernel->call('db:seed', ['--force' => true], $buffer);
    echo "----- seed output -----\n";
    echo $buffer->fetch();
    echo "----- exit code: $exitSeed -----\n\n";

    if ($exitMigrate === 0 && $exitSeed === 0) {
        echo "✓ Setup geslaagd!\n\n";
        echo "Je kunt nu inloggen op:\n";
        echo "  https://databasehub.sorai.nl/login\n\n";
        echo "  E-mail:     " . env('SUPERADMIN_EMAIL') . "\n";
        echo "  Wachtwoord: (zoals ingesteld in SUPERADMIN_PASSWORD in .env)\n\n";

        // Verwijder dit script zelf
        @unlink(__FILE__);
        echo "Dit setup-script heeft zichzelf verwijderd.\n";
    } else {
        echo "⚠ Setup gedeeltelijk gefaald — script NIET verwijderd zodat je opnieuw kunt proberen.\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Bestand: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString() . "\n";
}
