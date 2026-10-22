<?php
/**
 * Boels CORE — PHP-versiecheck (alleen-lezen), t.b.v. CVE-2026-109618 / CVE-2026-109619.
 * Open: https://databasehub.sorai.nl/__php.php?k=<DEPLOY_SECRET>
 */
$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) {
        $secret = trim($m[1]);
        break;
    }
}
if ($secret === null || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
header('Content-Type: text/plain; charset=utf-8');
echo "PHP-versie            : " . PHP_VERSION . "\n";
echo "SAPI                  : " . PHP_SAPI . "\n";
echo "session.upload_progress.enabled : " . var_export((bool) ini_get('session.upload_progress.enabled'), true) . "\n";
echo "session.use_strict_mode         : " . var_export((bool) ini_get('session.use_strict_mode'), true) . "\n";
echo "Servertijd            : " . date('Y-m-d H:i:s T') . "\n";
ob_start(); phpinfo(INFO_GENERAL); $info = strip_tags(ob_get_clean());
foreach (['Build Date', 'Build System', 'Loaded Configuration File'] as $k) {
    if (preg_match('/' . preg_quote($k, '/') . '\s*(?:=>)?\s*(.+)/', $info, $m)) {
        echo str_pad($k, 22) . ": " . trim($m[1]) . "\n";
    }
}
foreach (['/opt/alt/php84/usr/bin/lsphp', '/opt/alt/php84/usr/bin/php', PHP_BINARY] as $bin) {
    if (@file_exists($bin)) { echo str_pad('Binary ' . basename($bin), 22) . ": " . date('Y-m-d H:i:s', filemtime($bin)) . " (geïnstalleerd) $bin\n"; break; }
}
echo "\nAlle PHP-versies op deze server (binary-datum = bouwdatum van de CloudLinux-package):\n";
foreach (glob('/opt/alt/php*/usr/bin/lsphp') ?: [] as $b) {
    preg_match('#/php(\d)(\d+)/#', $b, $m);
    echo "  PHP {$m[1]}.{$m[2]}" . str_repeat(' ', 4 - strlen($m[2])) . ": " . date('Y-m-d H:i', filemtime($b)) . "\n";
}
