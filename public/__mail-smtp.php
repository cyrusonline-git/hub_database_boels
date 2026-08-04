<?php
/**
 * Boels CORE — Eenmalig: mail omzetten naar geauthenticeerd SMTP via een
 * echte sorai.nl-mailbox (lost afleverproblemen bij strenge filters op).
 *
 * Vooraf: maak in DirectAdmin de mailbox noreply@sorai.nl aan.
 * Open dit script met de DEPLOY_SECRET, vul het mailbox-wachtwoord in,
 * en het script patcht de .env, verstuurt een testmail en verwijdert zichzelf.
 */

$envFile = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $envFile = realpath($p); break; }
}
if (! $envFile) exit('env niet gevonden');
$contents = file_get_contents($envFile);
if (! preg_match('/^DEPLOY_SECRET=(.+)$/m', $contents, $m)
    || ! hash_equals(trim($m[1]), (string) ($_REQUEST['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
$key = htmlspecialchars($_REQUEST['k']);

// ---- Formulier tonen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8"><title>Mail via SMTP</title>
    <style>body{font-family:sans-serif;max-width:520px;margin:60px auto;padding:0 16px;}
    input,button{font-size:16px;padding:10px;width:100%;box-sizing:border-box;margin:6px 0;}
    button{background:#FF6600;color:#fff;border:none;border-radius:6px;font-weight:bold;}</style></head><body>
    <h2>Mail omzetten naar SMTP (noreply@sorai.nl)</h2>
    <p>Maak eerst in DirectAdmin de mailbox <strong>noreply@sorai.nl</strong> aan
    (E-mailaccounts &rarr; Account aanmaken). Vul hieronder het wachtwoord van die mailbox in.</p>
    <form method="POST">
        <input type="hidden" name="k" value="' . $key . '">
        <label>Wachtwoord van noreply@sorai.nl</label>
        <input type="password" name="wachtwoord" required>
        <label>Testmail sturen naar (bv. jouw boels.nl-adres)</label>
        <input type="email" name="test" value="wim.groeneweg@boels.nl" required>
        <button type="submit">Instellen &amp; testmail versturen</button>
    </form></body></html>';
    exit;
}

// ---- Patch uitvoeren
header('Content-Type: text/plain; charset=utf-8');
echo "Boels CORE — mail naar geauthenticeerd SMTP\n" . str_repeat('=', 50) . "\n\n";

$pw = (string) ($_POST['wachtwoord'] ?? '');
$test = (string) ($_POST['test'] ?? '');
if ($pw === '') exit("FOUT: geen wachtwoord ingevuld.\n");

$set = function (string $sleutel, string $waarde) use (&$contents) {
    $regel = $sleutel . '=' . $waarde;
    if (preg_match('/^' . preg_quote($sleutel, '/') . '=.*$/m', $contents)) {
        $contents = preg_replace('/^' . preg_quote($sleutel, '/') . '=.*$/m', $regel, $contents);
    } else {
        $contents = rtrim($contents) . "\n$regel\n";
    }
    echo "  [SET] " . ($sleutel === 'MAIL_PASSWORD' ? 'MAIL_PASSWORD=********' : $regel) . "\n";
};

// Laravel booten om de kandidaten live te testen
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        break;
    }
}

// Probeer servernamen tot er één werkt (certificaat moet de naam dekken)
$kandidaten = [
    ['mail.antagonist.nl', 465, 'ssl'],
    ['s241.webhostingserver.nl', 465, 'ssl'],
    ['mail.antagonist.nl', 587, 'tls'],
    ['mail.sorai.nl', 465, 'ssl'],
];
$gelukt = null;
foreach ($kandidaten as [$host, $poort, $enc]) {
    echo "Proberen: $host:$poort ($enc) ... ";
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => $host,
        'mail.mailers.smtp.port' => $poort,
        'mail.mailers.smtp.encryption' => $enc,
        'mail.mailers.smtp.scheme' => $enc === 'ssl' ? 'smtps' : null,
        'mail.mailers.smtp.username' => 'noreply@sorai.nl',
        'mail.mailers.smtp.password' => $pw,
        'mail.from.address' => 'noreply@sorai.nl',
        'mail.from.name' => 'Boels CORE',
    ]);
    app()->forgetInstance('mail.manager');
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Dit is een testmail van Boels CORE via geauthenticeerd SMTP (noreply@sorai.nl, via $host).\nVerstuurd: " . date('Y-m-d H:i:s'),
            fn ($m) => $m->to($test)->subject('Boels CORE testmail (SMTP) ' . date('H:i:s'))
        );
        echo "GELUKT!\n";
        $gelukt = [$host, $poort, $enc];
        break;
    } catch (\Throwable $e) {
        echo "nee (" . substr($e->getMessage(), 0, 90) . ")\n";
    }
}

if (! $gelukt) {
    echo "\nGeen enkele servernaam werkte. Controleer het wachtwoord (meest voorkomende\n";
    echo "oorzaak bij 'authentication failed') en probeer opnieuw — dit script blijft staan.\n";
    exit;
}

[$host, $poort, $enc] = $gelukt;
$set('MAIL_MAILER', 'smtp');
$set('MAIL_HOST', $host);
$set('MAIL_PORT', (string) $poort);
$set('MAIL_ENCRYPTION', $enc);
$set('MAIL_USERNAME', 'noreply@sorai.nl');
$set('MAIL_PASSWORD', '"' . addslashes($pw) . '"');
$set('MAIL_FROM_ADDRESS', 'noreply@sorai.nl');
$set('MAIL_FROM_NAME', '"Boels CORE"');
file_put_contents($envFile, $contents);
echo "\n.env opgeslagen met werkende server: $host:$poort ($enc).\n";

$buf = new \Symfony\Component\Console\Output\BufferedOutput;
app(\Illuminate\Contracts\Console\Kernel::class)->call('config:clear', [], $buf);
echo "Config cache gecleared.\n";

@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf verwijderd.\n";
echo "Check ook de spam-map van het testadres. Tip: zet in DirectAdmin ook DKIM aan\n";
echo "voor sorai.nl (E-mailbeheer) voor nóg betere aflevering.\n";
