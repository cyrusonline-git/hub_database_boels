<?php
/**
 * Boels CORE — Eenmalig: damen.sorai.nl toevoegen aan
 * SANCTUM_STATEFUL_DOMAINS. Sleutel = DEPLOY_SECRET uit de server-.env.
 * Verwijdert zichzelf na succes.
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
echo "Boels CORE — damen.sorai.nl toevoegen aan Sanctum\n" . str_repeat('=', 50) . "\n\n";

if (preg_match('/^SANCTUM_STATEFUL_DOMAINS=(.*)$/m', $contents, $sm)) {
    $domains = array_filter(array_map('trim', explode(',', $sm[1])));
    if (in_array('damen.sorai.nl', $domains)) {
        echo "damen.sorai.nl stond er al in.\n";
    } else {
        $domains[] = 'damen.sorai.nl';
        $line = 'SANCTUM_STATEFUL_DOMAINS=' . implode(',', $domains);
        $contents = preg_replace('/^SANCTUM_STATEFUL_DOMAINS=.*$/m', $line, $contents);
        file_put_contents($envFile, $contents);
        echo "Toegevoegd. Nieuwe waarde:\n$line\n";
    }
} else {
    exit("FOUT: SANCTUM_STATEFUL_DOMAINS niet gevonden in .env\n");
}

foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $buf = new \Symfony\Component\Console\Output\BufferedOutput;
        app(\Illuminate\Contracts\Console\Kernel::class)->call('config:clear', [], $buf);
        echo "Config cache gecleared.\n";
        break;
    }
}

@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf verwijderd.\n";
