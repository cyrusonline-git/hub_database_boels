<?php
/**
 * Eenmalig: meldt planning.sorai.nl aan als stateful SSO-domein in de CORE-.env
 * (SANCTUM_STATEFUL_DOMAINS), zodat de cookie-relay van de Transportplanner werkt.
 * Aanroep: https://databasehub.sorai.nl/__env-planner.php?k=<DEPLOY_SECRET>
 * Verwijdert zichzelf na succes.
 */
$envPad = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) { if (file_exists($p)) { $envPad = realpath($p); break; } }
if (!$envPad) exit('env niet gevonden');
$inhoud = file_get_contents($envPad);
if (!preg_match('/^DEPLOY_SECRET=(.+)$/m', $inhoud, $m) || !hash_equals(trim($m[1]), (string) ($_GET['k'] ?? ''))) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
$domein = 'planning.sorai.nl';
if (preg_match('/^SANCTUM_STATEFUL_DOMAINS=(.*)$/m', $inhoud, $sm)) {
    $lijst = array_values(array_filter(array_map('trim', explode(',', $sm[1]))));
    if (in_array($domein, $lijst, true)) { echo "$domein stond er al in.\n"; @unlink(__FILE__); exit; }
    $lijst[] = $domein;
    $inhoud = preg_replace('/^SANCTUM_STATEFUL_DOMAINS=.*$/m', 'SANCTUM_STATEFUL_DOMAINS=' . implode(',', $lijst), $inhoud);
    file_put_contents($envPad, $inhoud);
    @unlink(dirname($envPad) . '/bootstrap/cache/config.php');
    echo "$domein toegevoegd aan SANCTUM_STATEFUL_DOMAINS. CORE config-cache gewist.\n";
    @unlink(__FILE__);
} else {
    echo "LET OP: SANCTUM_STATEFUL_DOMAINS niet gevonden in de CORE-.env; handmatig toevoegen: $domein\n";
}
