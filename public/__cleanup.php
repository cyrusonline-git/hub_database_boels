<?php
/**
 * Boels CORE — Eenmalig opruimscript.
 * Verwijdert alle oude hulp/diagnose-scripts van de server en daarna zichzelf.
 *
 * Open: https://databasehub.sorai.nl/__cleanup.php?k=BOELS_CLEAN_2026
 */

$secret = 'BOELS_CLEAN_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
echo "Boels CORE — Opruimen hulpscripts\n" . str_repeat('=', 50) . "\n\n";

$targets = [
    '__mail-check.php', '__log-tail.php', '__data-check.php',
    '__fix-hierarchy.php', '__reset-login.php', '__env-patch.php',
    '__setup.php',
];

foreach ($targets as $t) {
    $path = __DIR__ . '/' . $t;
    if (file_exists($path)) {
        echo @unlink($path) ? "  [DEL]  $t\n" : "  [FOUT] $t kon niet verwijderd worden\n";
    } else {
        echo "  [SKIP] $t stond er niet (meer)\n";
    }
}

echo "\nBlijven staan (nodig voor deploys): __pull_deploy.php, __migrate.php\n";
@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf ook verwijderd.\n";
