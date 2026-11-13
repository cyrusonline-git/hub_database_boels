<?php
/**
 * Boels CORE — Smartview-export uploaden via script (zelfde verwerking als Beheer → Uploads Smartview).
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
if (! empty($_GET['diagnose'])) {
    // Welke app-databases kan CORE lezen (DataBrug)? Voor het managementoverzicht en acties.
    foreach (app(\App\Vraagbaak\DataBrug::class)->diagnose() as $d) {
        echo str_pad($d['bron'], 14).($d['leesbaar'] ? 'OK  ' : 'NEE ').str_pad((string) ($d['tabellen'] ?? '-'), 5).($d['fout'] ?: '').'  '.$d['pad']."\n";
    }
    if ($_GET['diagnose'] === '2') {
        // Cijfers per app zoals het managementoverzicht ze berekent (zonder cache)
        $ov = app(\App\Services\Sap\Overzicht::class);
        $m = new \ReflectionMethod($ov, 'apps');
        foreach ($m->invoke($ov) as $k => $a) {
            echo "\n".str_pad($k, 14).($a['ok'] ? json_encode($a['cijfers'], JSON_UNESCAPED_UNICODE) : 'FOUT: '.($a['fout'] ?? 'query mislukt'));
        }
        echo "\n";
    }
    exit;
}
if (! empty($_GET['contract'])) {
    $nr = preg_replace('/\D/', '', (string) $_GET['contract']);
    $hd = app(\App\Services\Sap\HireData::class);
    $c = $hd->contract($nr);
    echo "WOW: ".($c ? count($c['regels'])." regels in huur bij {$c['customer_name']}, hire_date ".collect($c['regels'])->min('hire_date')?->format('d-m-Y')." t/m ".collect($c['regels'])->max('hire_date')?->format('d-m-Y').", weekhuur € {$c['weekhuur']}" : 'niet in huur')."\n";
    $oo = $hd->openOrder($nr);
    echo "8.7: ".($oo ? "{$oo['soort']} lever {$oo['delivery_date']} status {$oo['contract_status']} pick {$oo['picked']}/{$oo['total']} → nog te picken {$oo['nog_te_picken']} · verkoper {$oo['sales_person']} · 2.6-regels ".count($oo['regels_26']) : 'geen open order')."\n";
    $rij = app(\App\Services\Sap\PlanningOverzicht::class)->rijen(['alles' => 1])->firstWhere('order_number', $nr);
    echo "Overzicht: ".($rij ? json_encode(['bron' => $rij->bron, 'bijhuur' => $rij->bijhuur, 'in_huur_regels' => $rij->in_huur_regels, 'delivery_date' => $rij->delivery_date, 'pick_status' => $rij->pick_status, 'nog_te_picken' => $rij->nog_te_picken, 'te_laat' => $rij->te_laat]) : 'niet in planning')."\n";
    exit;
}
if (! empty($_GET['geleverd'])) {
    $w = \App\Models\SapUpload::laatste('wow'); $r = \App\Models\SapUpload::laatste('reserveringen');
    echo 'WOW ', $w?->created_at, ' | 3.4 ', $r?->created_at, ' | 8.7 ', \App\Models\SapUpload::laatste('planning')?->created_at, "\n";
    $wow = $w ? \App\Models\HireLine::where('upload_id', $w->id)->selectRaw('contract_number, COUNT(*) n, SUM(qty_out) q, MIN(hire_date) hd, MAX(status) st')->groupBy('contract_number')->get()->keyBy('contract_number') : collect();
    $r34 = $r ? \App\Models\ReservationLine::where('upload_id', $r->id)->selectRaw('contract_number, COUNT(*) n, SUM(qty) q, MAX(contract_status) cs')->groupBy('contract_number')->get()->keyBy('contract_number') : collect();
    $van = now()->subDays((int) ($_GET['dagen'] ?? 3))->toDateString(); $tm = now()->addDay()->toDateString();
    $rows = app(\App\Services\Sap\PlanningOverzicht::class)->rijen(['alles' => 1])->filter(fn ($x) => ! in_array($x->soort, ['transfer_in', 'transfer_uit']) && $x->delivery_date >= $van && $x->delivery_date <= $tm);
    printf("%-11s %-8s %-10s %-11s %-9s %-9s %-16s %-9s %-10s %-6s %s\n", 'order', 'soort', 'lever', '8.7 status', 'pick', 'WOW n/q', 'WOW status', '3.4 n/q', '3.4 status', 'nieuw', 'GELEVERD');
    foreach ($rows->sortBy('delivery_date') as $x) { $wv = $wow[$x->order_number] ?? null; $rv = $r34[$x->order_number] ?? null;
        printf("%-11s %-8s %-10s %-11s %-9s %-9s %-16s %-9s %-10s %-6s %s\n", $x->order_number, $x->soort, $x->delivery_date, $x->contract_status ?? '-', $x->total === null ? '-' : (($x->picked + 0).'/'.($x->total + 0)), $wv ? ($wv->n.'/'.($wv->q + 0)) : '-', $wv ? mb_substr($wv->st, 0, 16) : '-', $rv ? ($rv->n.'/'.($rv->q + 0)) : '-', $rv?->cs ?? '-', count($x->nieuwe_regels).'/'.(int) $x->bijhuur, $x->geleverd ? 'JA' : '-'); }
    exit;
}
if (! empty($_GET['po'])) {
    foreach (\App\Models\ReferenceRequest::orderByDesc('id')->take(10)->get() as $v) {
        echo $v->id, ' ', $v->contract_number, ' aan=', var_export($v->gevraagd_aan, true), ' raw=', var_export($v->getRawOriginal('gevraagd_op'), true), ' cast=', var_export($v->gevraagd_op?->toDateString(), true), ' user=', $v->user_id, ' created=', $v->created_at, "\n";
    }
    foreach (\App\Models\ReferenceNote::orderByDesc('updated_at')->take(8)->get() as $n) {
        echo 'note ', $n->contract_number, ' status=', $n->status, ' opmerking=', var_export($n->opmerking, true), ' opgevraagd_op=', $n->opgevraagd_op?->toDateString(), ' user=', $n->user_id, ' updated=', $n->updated_at, "\n";
    }
    echo json_encode(app(\App\Services\Sap\ReferentieBewaking::class)->verzoeken(\App\Models\ReferenceRequest::pluck('contract_number')->unique()->values()->all()), JSON_UNESCAPED_UNICODE), "\n";
    exit;
}
if (! empty($_GET['maak_kaarten'])) {
    $res = app(\App\Services\Sap\SapUserCodes::class)->maakKaartenVoorGebruikers();
    echo count($res['aangemaakt'])." medewerkerkaart(en) aangemaakt:\n";
    foreach ($res['aangemaakt'] as $n) {
        echo "  - $n\n";
    }
    echo $res['gekoppeld']." Smartview-code(s) alsnog automatisch gekoppeld.\n";
    foreach (\App\Models\SapUserCode::with('employee')->orderBy('code')->get() as $c) {
        echo str_pad($c->code, 26).str_pad($c->bron, 10).($c->employee?->name ?? '—')."\n";
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
