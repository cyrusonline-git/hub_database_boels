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

$set('MAIL_MAILER', 'smtp');
$set('MAIL_HOST', 'mail.sorai.nl');
$set('MAIL_PORT', '465');
$set('MAIL_ENCRYPTION', 'ssl');
$set('MAIL_USERNAME', 'noreply@sorai.nl');
$set('MAIL_PASSWORD', '"' . addslashes($pw) . '"');
$set('MAIL_FROM_ADDRESS', 'noreply@sorai.nl');
$set('MAIL_FROM_NAME', '"Boels CORE"');
file_put_contents($envFile, $contents);
echo "\n.env opgeslagen.\n";

foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $buf = new \Symfony\Component\Console\Output\BufferedOutput;
        app(\Illuminate\Contracts\Console\Kernel::class)->call('config:clear', [], $buf);
        echo "Config cache gecleared.\n\n";
        break;
    }
}

if ($test !== '') {
    echo "Testmail naar $test ... ";
    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Dit is een testmail van Boels CORE via geauthenticeerd SMTP (noreply@sorai.nl).\nVerstuurd: " . date('Y-m-d H:i:s'),
            fn ($m) => $m->to($test)->subject('Boels CORE testmail (SMTP) ' . date('H:i:s'))
        );
        echo "OK — verstuurd zonder fouten.\n";
    } catch (\Throwable $e) {
        echo "FOUT: " . $e->getMessage() . "\n";
        echo "\nWachtwoord fout of mailbox bestaat niet? Herlaad de pagina en probeer opnieuw —\n";
        echo "dit script blijft staan tot het lukt.\n";
        exit;
    }
}

@unlink(__FILE__);
echo "\n✓ Klaar. Dit script heeft zichzelf verwijderd.\n";
echo "Check ook de spam-map van het testadres. Tip: zet in DirectAdmin ook DKIM aan\n";
echo "voor sorai.nl (E-mailbeheer) voor nóg betere aflevering.\n";
