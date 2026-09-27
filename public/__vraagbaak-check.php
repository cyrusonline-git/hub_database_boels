<?php
/**
 * Boels CORE — Vraagbaak-diagnose (blijft staan): per app-database of hij gevonden en leesbaar is.
 * Aanroep: /__vraagbaak-check.php?k=<DEPLOY_SECRET>
 */
$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) { $secret = trim($m[1]); break; }
}
if ($secret === null || $secret === '' || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
$larDir = is_dir(__DIR__ . '/../laravel_app') ? __DIR__ . '/../laravel_app' : __DIR__ . '/..';
require $larDir . '/vendor/autoload.php';
$app = require $larDir . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "Vraagbaak — bronnen (domains-map: " . \App\Vraagbaak\DataBrug::domainsDir() . ")\n\n";
foreach ((new \App\Vraagbaak\DataBrug())->diagnose() as $r) {
    printf("%-14s %-9s %-9s %-10s %s\n", $r['bron'], $r['gevonden'] ? 'gevonden' : 'ONTBREEKT', $r['leesbaar'] ? 'leesbaar' : 'NIET', $r['tabellen'] !== null ? $r['tabellen'] . ' tab.' : '', $r['fout'] ?: $r['pad']);
}
echo "\nVragen in catalogus: " . count(\App\Vraagbaak\Catalogus::alle()) . "\n";
