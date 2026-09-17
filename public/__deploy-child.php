<?php
/**
 * Boels CORE — Child-app installeren/bijwerken op HETZELFDE hostingaccount.
 *
 * Voor Laravel-child-apps die (net als CORE) via GitHub Releases deployen.
 * Downloadt de laatste release van de app-repo en pakt die uit in
 * domains/<domein>/public_html en domains/<domein>/laravel_app. Bij de eerste
 * keer wordt de .env van de app aangemaakt (eigen APP_KEY; DEPLOY_SECRET en
 * de SMTP-mailinstellingen worden uit de CORE-.env overgenomen), daarna
 * migreert hij en cleart hij de caches. Herhaalbaar; blijft staan.
 *
 * Open: https://databasehub.sorai.nl/__deploy-child.php?k=<DEPLOY_SECRET>&app=voorraad[&tag=...]
 */
$apps = [
    'voorraad' => ['domein' => 'voorraad.sorai.nl', 'repo' => 'cyrusonline-git/voorraad-tool', 'naam' => 'Voorraad tool'],
];

$coreEnv = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $coreEnv = realpath($p); break; }
}
if (! $coreEnv) exit('env niet gevonden');
$coreEnvInhoud = file_get_contents($coreEnv);
if (! preg_match('/^DEPLOY_SECRET=(.+)$/m', $coreEnvInhoud, $m)
    || ! hash_equals(trim($m[1]), (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
$secret = trim($m[1]);
$appKey = (string) ($_GET['app'] ?? '');
if (! isset($apps[$appKey])) {
    exit('Onbekende app. Bekend: ' . implode(', ', array_keys($apps)) . "\n");
}
$app = $apps[$appKey];
$tag = $_GET['tag'] ?? 'latest';

@set_time_limit(300);
@ini_set('memory_limit', '256M');
header('Content-Type: text/plain; charset=utf-8');

// domains/databasehub.sorai.nl/public_html → domains/<domein>
$domainsDir = dirname(dirname(__DIR__));
$appRoot = $domainsDir . '/' . $app['domein'];
$publicDir = $appRoot . '/public_html';
$larDir = $appRoot . '/laravel_app';

echo "Boels CORE — Child-app deploy: {$app['naam']}\n" . str_repeat('=', 60) . "\n\n";
echo "Repo:      {$app['repo']}\nTag:       $tag\nDoel:      $appRoot\nTijd:      " . date('Y-m-d H:i:s') . "\n\n";
if (! is_dir($appRoot)) {
    exit("FOUT: map $appRoot bestaat niet — maak het domein eerst aan in DirectAdmin.\n");
}
foreach ([$publicDir, $larDir] as $d) {
    if (! is_dir($d) && ! @mkdir($d, 0755, true)) exit("FOUT: kan $d niet aanmaken\n");
}

// 1. Release-info
echo "[1/5] Release info ophalen...\n";
$apiUrl = $tag === 'latest'
    ? "https://api.github.com/repos/{$app['repo']}/releases/latest"
    : "https://api.github.com/repos/{$app['repo']}/releases/tags/$tag";
$ctx = stream_context_create(['http' => ['header' => "User-Agent: Boels-CORE-ChildDeploy\r\nAccept: application/vnd.github+json\r\n", 'timeout' => 30]]);
$json = @file_get_contents($apiUrl, false, $ctx);
if (! $json) exit("FOUT: kon GitHub API niet bereiken ($apiUrl) — is de release al gebouwd?\n");
$release = json_decode($json, true);
if (! isset($release['assets'])) exit("FOUT: geen assets in release: " . substr($json, 0, 200) . "\n");
echo "      → Release: " . ($release['tag_name'] ?? '?') . "\n\n";

// 2. Downloaden
echo "[2/5] Zips downloaden...\n";
$tmpDir = __DIR__ . '/_deploy_tmp_' . $appKey;
if (! is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
$downloads = [];
foreach ($release['assets'] as $asset) {
    if (! in_array($asset['name'], ['laravel_app.zip', 'public_html.zip'])) continue;
    $localPath = $tmpDir . '/' . $asset['name'];
    echo "      → {$asset['name']} (" . round($asset['size'] / 1024 / 1024, 1) . " MB) ... ";
    $fp = fopen($localPath, 'w');
    $ch = curl_init($asset['browser_download_url']);
    curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'Boels-CORE-ChildDeploy', CURLOPT_TIMEOUT => 180]);
    $ok = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    if (! $ok || $code !== 200) exit("FAIL (HTTP $code)\n");
    echo "OK\n";
    $downloads[$asset['name']] = $localPath;
}
if (count($downloads) < 2) exit("FOUT: niet beide zips gevonden in de release.\n");

// 3. Uitpakken
echo "\n[3/5] Uitpakken...\n";
foreach (['public_html.zip' => $publicDir, 'laravel_app.zip' => $larDir] as $zipNaam => $doel) {
    $z = new ZipArchive;
    if ($z->open($downloads[$zipNaam]) !== true) exit("FOUT: kon $zipNaam niet openen\n");
    $z->extractTo($doel);
    $z->close();
    echo "      → $zipNaam uitgepakt in $doel\n";
}
// DirectAdmin zet bij een nieuw domein een placeholder index.html neer die vóór index.php gaat
foreach (['index.html', 'index.htm'] as $ph) {
    $pad = $publicDir . '/' . $ph;
    if (file_exists($pad) && stripos((string) file_get_contents($pad), 'tijdelijke') !== false) {
        @unlink($pad);
        echo "      → placeholder $ph van DirectAdmin verwijderd\n";
    }
}
foreach (['storage/app', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'database'] as $sub) {
    if (! is_dir("$larDir/$sub")) @mkdir("$larDir/$sub", 0755, true);
}

// 4. .env (alleen de eerste keer)
echo "\n[4/5] Configuratie...\n";
$envPad = $larDir . '/.env';
if (! file_exists($envPad)) {
    $voorbeeld = file_exists($larDir . '/.env.example') ? file_get_contents($larDir . '/.env.example') : '';
    $waarden = [
        'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
        'DEPLOY_SECRET' => $secret,
        'APP_URL' => 'https://' . $app['domein'],
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
    ];
    // Mail-instellingen van CORE overnemen (zelfde mailbox noreply@sorai.nl)
    foreach (['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_SCHEME', 'MAIL_FROM_ADDRESS'] as $k) {
        if (preg_match('/^' . $k . '=(.*)$/m', $coreEnvInhoud, $mm)) $waarden[$k] = trim($mm[1]);
    }
    $env = $voorbeeld;
    foreach ($waarden as $k => $v) {
        $regel = $k . '=' . $v;
        $env = preg_match('/^' . $k . '=.*$/m', $env) ? preg_replace('/^' . $k . '=.*$/m', $regel, $env) : $env . "\n$regel";
    }
    file_put_contents($envPad, $env);
    echo "      → .env aangemaakt (nieuwe APP_KEY, DEPLOY_SECRET = die van CORE, mail uit CORE)\n";
} else {
    echo "      → .env bestaat al, ongewijzigd gelaten\n";
}
$dbPad = $larDir . '/database/database.sqlite';
if (! file_exists($dbPad)) { touch($dbPad); echo "      → lege SQLite-database aangemaakt\n"; }

// 5. Migreren + caches
echo "\n[5/5] Migreren + cache...\n";
try {
    require $larDir . '/vendor/autoload.php';
    $laravel = require $larDir . '/bootstrap/app.php';
    $kernel = $laravel->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $buf = new \Symfony\Component\Console\Output\BufferedOutput;
    $exit = $kernel->call('migrate', ['--force' => true], $buf);
    echo "      → migrate (exit $exit):\n" . preg_replace('/^/m', '        ', trim($buf->fetch())) . "\n";
    foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear', 'view:cache'] as $cmd) {
        $kernel->call($cmd, [], $buf);
    }
    echo "      → caches gecleared, views gecompileerd\n";
} catch (\Throwable $e) {
    echo "      → WAARSCHUWING: " . $e->getMessage() . "\n";
}

foreach ($downloads as $p) @unlink($p);
@rmdir($tmpDir);
echo "\n" . str_repeat('=', 60) . "\n✓ {$app['naam']} staat op https://{$app['domein']}\n";
echo "Volgende deploys kunnen ook rechtstreeks: https://{$app['domein']}/__pull_deploy.php?k=<DEPLOY_SECRET>\n";
