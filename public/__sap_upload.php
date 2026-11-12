<?php
/**
 * Boels CORE — SAP-export uploaden via script (zelfde verwerking als Beheer → Uploads SAP).
 *
 *   curl -F file=@"boels_whatsoutwhere_incllimitedmode.xls" "https://databasehub.sorai.nl/__sap_upload.php?k=<DEPLOY_SECRET>"
 *   optioneel &soort=wow|factuurrun|planning|reserveringen (controle), &status=1 (alleen laatste stand tonen)
 *
 * Beveiligd met DEPLOY_SECRET uit de server-.env. Blijft staan.
 */
$secret = null;
foreach ([__DIR__.'/../laravel_app/.env', __DIR__.'/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) {
        $secret = trim($m[1]);
        break;
    }
}
if ($secret === null || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}
@set_time_limit(600);
header('Content-Type: text/plain; charset=utf-8');
$root = null;
foreach ([__DIR__.'/../laravel_app', __DIR__.'/..'] as $p) {
    if (file_exists($p.'/vendor/autoload.php')) {
        $root = realpath($p);
        break;
    }
}
if (! $root) {
    exit("FOUT: Laravel niet gevonden.\n");
}
require $root.'/vendor/autoload.php';
$app = require_once $root.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (! empty($_GET['status'])) {
    foreach ((array) config('sap.soorten') as $soort => $cfg) {
        $l = \App\Models\SapUpload::laatste($soort);
        echo str_pad($soort, 16).($l ? $l->created_at->format('d-m-Y H:i').'  '.$l->regels.' regels, '.$l->contracten.' contracten  '.json_encode($l->samenvatting, JSON_UNESCAPED_UNICODE) : 'nog nooit')."\n";
    }
    exit;
}
if (empty($_FILES['file']['tmp_name'])) {
    exit("Geen bestand (veld 'file').\n");
}
$naam = (string) ($_FILES['file']['name'] ?? 'upload.xlsx');
$tmp = sys_get_temp_dir().'/sap_'.bin2hex(random_bytes(6)).'.'.strtolower(pathinfo($naam, PATHINFO_EXTENSION) ?: 'xlsx');
move_uploaded_file($_FILES['file']['tmp_name'], $tmp);
try {
    $res = app(\App\Services\Sap\SapImportService::class)->importeer($tmp, $naam, null, $_GET['soort'] ?? null);
    echo ($res['ok'] ? 'OK   ' : 'FOUT ').$res['melding']."\n";
    if (! empty($res['upload'])) {
        echo '     upload #'.$res['upload']->id.' · rapportdatum '.$res['upload']->rapportdatum?->format('d-m-Y').' · '.json_encode($res['upload']->samenvatting, JSON_UNESCAPED_UNICODE)."\n";
    }
} catch (\Throwable $e) {
    echo 'EXCEPTION: '.$e->getMessage()."\n";
} finally {
    @unlink($tmp);
}
