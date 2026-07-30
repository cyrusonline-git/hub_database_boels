<?php
/**
 * Boels CORE — Log tail (diagnose).
 *
 * Open: https://databasehub.sorai.nl/__log-tail.php?k=BOELS_LOG_2026
 * Optioneel: &n=100 (aantal regels), &grep=woord (filter)
 * Blijft staan; alleen-lezen.
 */

$secret = 'BOELS_LOG_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
$root = null;
foreach ($candidates as $p) {
    if (is_dir($p . '/storage/logs')) { $root = realpath($p); break; }
}
if (! $root) exit("FOUT: storage/logs niet gevonden.\n");

$n = min(500, max(10, (int) ($_GET['n'] ?? 120)));
$grep = $_GET['grep'] ?? null;

$files = glob($root . '/storage/logs/*.log');
usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

if (! $files) {
    echo "Geen logbestanden in {$root}/storage/logs.\n";
    echo "PHP error log locatie: " . (ini_get('error_log') ?: '(niet ingesteld)') . "\n";
    exit;
}

$file = $files[0];
echo "Log: $file (" . round(filesize($file) / 1024) . " KB, laatst gewijzigd " . date('Y-m-d H:i:s', filemtime($file)) . ")\n";
echo str_repeat('=', 70) . "\n";

$lines = file($file);
if ($grep) {
    $lines = array_values(array_filter($lines, fn ($l) => stripos($l, $grep) !== false));
}
foreach (array_slice($lines, -$n) as $line) {
    echo rtrim(substr($line, 0, 500)) . "\n";
}
