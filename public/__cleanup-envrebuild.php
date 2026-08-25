<?php
/**
 * Boels CORE — Eenmalig: verwijdert __env-rebuild.php van de server.
 * Dat herstelscript had een vaste sleutel die in de publieke repo staat,
 * waardoor buitenstaanders het .env-bestand konden overschrijven.
 * Sleutel = DEPLOY_SECRET uit de server-.env. Verwijdert zichzelf na afloop.
 */

$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) {
        $secret = trim($m[1]);
        break;
    }
}
if (! $secret || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
echo "Boels CORE — Opruimen __env-rebuild.php\n" . str_repeat('=', 50) . "\n\n";

$target = __DIR__ . '/__env-rebuild.php';
if (file_exists($target)) {
    echo @unlink($target) ? "✓ __env-rebuild.php verwijderd.\n" : "✗ Kon __env-rebuild.php NIET verwijderen — check bestandsrechten.\n";
} else {
    echo "__env-rebuild.php stond er niet (al opgeruimd).\n";
}

echo @unlink(__FILE__) ? "✓ Dit script heeft zichzelf verwijderd.\n" : "✗ Kon zichzelf niet verwijderen.\n";
