<?php
/**
 * Boels CORE — Mail diagnose & fix script.
 *
 * Open: https://databasehub.sorai.nl/__mail-check.php?k=BOELS_MAIL_2026
 *   → toont mailconfig + laatste mail-gerelateerde logregels
 *
 * &send=1            → verstuurt een testmail
 * &patch=sendmail    → zet MAIL_MAILER=sendmail in .env + test
 * &patch=smtp        → zet MAIL_MAILER=smtp (localhost:25) in .env + test
 * &to=адres          → ander testadres (default w_groeneweg@hotmail.com)
 */

$secret = 'BOELS_MAIL_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
echo "Boels CORE — Mail diagnose\n" . str_repeat('=', 50) . "\n\n";

$candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
$root = null;
foreach ($candidates as $p) {
    if (file_exists($p . '/vendor/autoload.php')) { $root = realpath($p); break; }
}
if (! $root) exit("FOUT: Laravel niet gevonden.\n");

// ---- Optioneel: .env patchen vóór het booten
$patch = $_GET['patch'] ?? null;
if ($patch && in_array($patch, ['sendmail', 'smtp'])) {
    $envFile = $root . '/.env';
    $contents = file_get_contents($envFile);
    $set = function (string $key, string $value) use (&$contents) {
        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $contents)) {
            $contents = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', "$key=$value", $contents);
            echo "  [SET] $key=$value\n";
        } else {
            $contents = rtrim($contents) . "\n$key=$value\n";
            echo "  [ADD] $key=$value\n";
        }
    };
    echo "Patch .env → mailer: $patch\n";
    $set('MAIL_MAILER', $patch);
    if ($patch === 'smtp') {
        $set('MAIL_HOST', 'localhost');
        $set('MAIL_PORT', '25');
        $set('MAIL_ENCRYPTION', 'null');
        $set('MAIL_USERNAME', 'null');
        $set('MAIL_PASSWORD', 'null');
    }
    $set('MAIL_FROM_ADDRESS', 'noreply@sorai.nl');
    $set('MAIL_FROM_NAME', '"Boels CORE"');
    file_put_contents($envFile, $contents);
    echo "  .env opgeslagen.\n\n";
}

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if ($patch) {
    $buf = new \Symfony\Component\Console\Output\BufferedOutput;
    $kernel->call('config:clear', [], $buf);
    echo "Config cache gecleared.\n\n";
}

// ---- Huidige mailconfig tonen
echo "Actieve mailconfig:\n";
echo "  default mailer : " . config('mail.default') . "\n";
echo "  host           : " . config('mail.mailers.smtp.host') . "\n";
echo "  port           : " . config('mail.mailers.smtp.port') . "\n";
echo "  encryption     : " . (config('mail.mailers.smtp.encryption') ?? config('mail.mailers.smtp.scheme') ?? '-') . "\n";
echo "  username       : " . (config('mail.mailers.smtp.username') ? '(ingesteld)' : '(leeg)') . "\n";
echo "  from           : " . config('mail.from.address') . " (" . config('mail.from.name') . ")\n";
echo "  sendmail path  : " . config('mail.mailers.sendmail.path') . "\n\n";

// ---- Testmail
if (isset($_GET['send']) || $patch) {
    $to = $_GET['to'] ?? 'w_groeneweg@hotmail.com';
    echo "Testmail versturen naar $to ... ";
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Dit is een testmail van Boels CORE (databasehub.sorai.nl).\nVerstuurd: " . date('Y-m-d H:i:s'),
            fn ($m) => $m->to($to)->subject('Boels CORE testmail ' . date('H:i:s'))
        );
        echo "OK — geen exception.\n\n";
    } catch (\Throwable $e) {
        echo "FOUT:\n  " . get_class($e) . ": " . $e->getMessage() . "\n\n";
    }
}

// ---- Log tail
$log = $root . '/storage/logs/laravel.log';
if (file_exists($log)) {
    echo "Laatste mail/error-regels uit laravel.log:\n";
    $lines = file($log);
    $tail = array_slice($lines, -400);
    $shown = 0;
    foreach (array_reverse($tail) as $line) {
        if (preg_match('/mail|smtp|swift|symfony.*transport|stream_socket/i', $line)) {
            echo "  " . trim(substr($line, 0, 200)) . "\n";
            if (++$shown >= 15) break;
        }
    }
    if (! $shown) echo "  (geen mail-gerelateerde regels in de laatste 400 regels)\n";
} else {
    echo "Geen laravel.log gevonden.\n";
}

echo "\nKlaar. Dit script blijft staan tot de mail werkt.\n";
