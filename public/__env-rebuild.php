<?php
/**
 * Boels CORE — Eenmalig .env-herstel na dataverlies op de server.
 * Bouwt het complete instellingenbestand opnieuw op: jij vult alleen het
 * database-wachtwoord (DirectAdmin → MySQL Management) en het wachtwoord
 * van de mailbox noreply@sorai.nl in. Sleutels (APP_KEY, DEPLOY_SECRET)
 * worden vers gegenereerd. Test direct de database en mail.
 * Verwijdert zichzelf na een geslaagde databasetest.
 */

$FORM_KEY = '20ae53b357cbf4ec8ab032f3bd444a3646dcba59';
if (!hash_equals($FORM_KEY, (string)($_REQUEST['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
$key = htmlspecialchars($FORM_KEY);

$laravelRoot = null;
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) { $laravelRoot = realpath($p); break; }
}
if (!$laravelRoot) exit("FOUT: laravel_app niet gevonden — draai eerst de pull-deploy.\n");
$envFile = $laravelRoot . '/.env';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $bestaat = file_exists($envFile) ? '<p style="color:#b00;"><strong>Let op:</strong> er staat al een .env — die wordt overschreven.</p>' : '';
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8"><title>.env herstellen</title>
    <style>body{font-family:sans-serif;max-width:560px;margin:50px auto;padding:0 16px;}
    input,button{font-size:16px;padding:10px;width:100%;box-sizing:border-box;margin:5px 0;}
    label{font-weight:bold;font-size:14px;} small{color:#666;}
    button{background:#FF6600;color:#fff;border:none;border-radius:6px;font-weight:bold;}</style></head><body>
    <h2>Boels CORE — instellingen herstellen</h2>' . $bestaat . '
    <form method="POST">
        <input type="hidden" name="k" value="' . $key . '">
        <label>Databasenaam</label>
        <input type="text" name="db_naam" value="deb2003831_hub_database_boels" required>
        <label>Database-gebruiker</label>
        <input type="text" name="db_user" value="deb2003831_hub_database_boels" required>
        <label>Database-wachtwoord</label>
        <input type="password" name="db_pass" required>
        <small>Te vinden of te resetten in DirectAdmin &rarr; MySQL Management.</small>
        <label>Wachtwoord mailbox noreply@sorai.nl</label>
        <input type="password" name="mail_pass">
        <small>Leeg laten kan — dan werkt alles behalve mail (later in te stellen).</small>
        <button type="submit">Herstellen &amp; testen</button>
    </form></body></html>';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "Boels CORE — .env herstellen\n" . str_repeat('=', 50) . "\n\n";

$dbNaam = trim($_POST['db_naam'] ?? '');
$dbUser = trim($_POST['db_user'] ?? '');
$dbPass = (string)($_POST['db_pass'] ?? '');
$mailPass = (string)($_POST['mail_pass'] ?? '');

// ---- Eerst de database testen vóór we iets wegschrijven
echo "Databaseverbinding testen ... ";
try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=$dbNaam;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8,
    ]);
    echo "OK\n\nData-controle (is alles er nog?):\n";
    foreach (['users', 'employees', 'customers', 'machines', 'machine_subgroups'] as $t) {
        try {
            $n = $pdo->query("SELECT count(*) FROM `$t`")->fetchColumn();
            printf("  %-20s %s rijen\n", $t, number_format((int)$n, 0, ',', '.'));
        } catch (Exception $e) {
            printf("  %-20s FOUT: %s\n", $t, substr($e->getMessage(), 0, 60));
        }
    }
} catch (Exception $e) {
    echo "MISLUKT: " . $e->getMessage() . "\n\n";
    echo "Controleer naam/gebruiker/wachtwoord (DirectAdmin → MySQL Management)\n";
    echo "en probeer opnieuw — er is niets gewijzigd, dit formulier blijft staan.\n";
    exit;
}

// ---- .env opbouwen
$appKey = 'base64:' . base64_encode(random_bytes(32));
$deploySecret = bin2hex(random_bytes(20));
$mailBlok = $mailPass !== ''
    ? "MAIL_MAILER=smtp\nMAIL_HOST=mail.antagonist.nl\nMAIL_PORT=465\nMAIL_ENCRYPTION=ssl\nMAIL_USERNAME=noreply@sorai.nl\nMAIL_PASSWORD=\"" . addslashes($mailPass) . "\"\nMAIL_FROM_ADDRESS=noreply@sorai.nl\nMAIL_FROM_NAME=\"Boels CORE\""
    : "MAIL_MAILER=log\nMAIL_FROM_ADDRESS=noreply@sorai.nl\nMAIL_FROM_NAME=\"Boels CORE\"";

$env = <<<ENV
APP_NAME="Boels CORE Platform"
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://databasehub.sorai.nl

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$dbNaam
DB_USERNAME=$dbUser
DB_PASSWORD="{$dbPass}"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_COOKIE=boels_core_platform_session
SESSION_DOMAIN=.sorai.nl
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

SANCTUM_STATEFUL_DOMAINS=databasehub.sorai.nl,hub.sorai.nl,fleet.sorai.nl,sales.sorai.nl,schade.sorai.nl,ai.sorai.nl,werkbon.sorai.nl,monteurs.sorai.nl,rapportage.sorai.nl,scanner.sorai.nl,offertes.sorai.nl,tankapp.sorai.nl

$mailBlok

DEPLOY_SECRET=$deploySecret
ENV;

// Eventuele oude .env eerst veiligstellen
if (file_exists($envFile)) {
    @copy($envFile, $envFile . '.backup-' . date('Ymd-His'));
    echo "\nBestaande .env veiliggesteld als .env.backup-*\n";
}
file_put_contents($envFile, $env . "\n");
echo "\n.env geschreven.\n";

// ---- Laravel booten, cache clear, migraties
require $laravelRoot . '/vendor/autoload.php';
$app = require_once $laravelRoot . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$buf = new \Symfony\Component\Console\Output\BufferedOutput;
$kernel->call('config:clear', [], $buf);
$kernel->call('route:clear', [], $buf);
$kernel->call('view:clear', [], $buf);
echo "Caches gecleared.\n";
$kernel->call('migrate', ['--force' => true], $buf);
echo "Migraties: " . trim(substr($buf->fetch(), 0, 300)) . "\n";

// ---- Mailtest
if ($mailPass !== '') {
    echo "\nTestmail naar wim.groeneweg@boels.nl ... ";
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Boels CORE is hersteld — mail werkt weer. " . date('Y-m-d H:i:s'),
            fn ($m) => $m->to('wim.groeneweg@boels.nl')->subject('Boels CORE hersteld')
        );
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "FOUT: " . substr($e->getMessage(), 0, 120) . "\n(mail later opnieuw instellen; de rest werkt)\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "BEWAAR DIT — je nieuwe deploy-URL (geef hem ook aan Claude):\n\n";
echo "https://databasehub.sorai.nl/__pull_deploy.php?k=$deploySecret\n";
echo str_repeat('=', 50) . "\n";

@unlink(__FILE__);
echo "\n✓ Klaar. Dit formulier heeft zichzelf verwijderd.\n";
echo "Open nu https://databasehub.sorai.nl en log in.\n";
