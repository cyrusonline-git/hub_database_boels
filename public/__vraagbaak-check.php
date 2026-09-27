<?php
/**
 * Boels CORE — Vraagbaak-diagnose (blijft staan): per app-database of hij gevonden en leesbaar is.
 * Aanroep: /__vraagbaak-check.php?k=<DEPLOY_SECRET>
 */
$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) { $secret = trim($m[1]); break; }
}
if ($secret === null || $secret === '' || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
$larDir = is_dir(__DIR__ . '/../laravel_app') ? __DIR__ . '/../laravel_app' : __DIR__ . '/..';
require $larDir . '/vendor/autoload.php';
$app = require $larDir . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "Vraagbaak — bronnen (domains-map: " . \App\Vraagbaak\DataBrug::domainsDir() . ")\n\n";
foreach ((new \App\Vraagbaak\DataBrug())->diagnose() as $r) {
    printf("%-14s %-9s %-9s %-10s %s\n", $r['bron'], $r['gevonden'] ? 'gevonden' : 'ONTBREEKT', $r['leesbaar'] ? 'leesbaar' : 'NIET', $r['tabellen'] !== null ? $r['tabellen'] . ' tab.' : '', $r['fout'] ?: $r['pad']);
}
echo "\nVragen in catalogus: " . count(\App\Vraagbaak\Catalogus::alle()) . "\n";

// ?test=1 → elke catalogusvraag één keer draaien met standaardparameters (read-only) en fouten tonen
if (! empty($_GET['test'])) {
    echo "\nTest van alle vragen (standaardperiode, geen depot):\n";
    $brug = new \App\Vraagbaak\DataBrug();
    $ok = 0; $fout = 0;
    foreach (\App\Vraagbaak\Catalogus::alle() as $v) {
        $p = \App\Vraagbaak\Herkenner::parameters($v->titel, $v);
        if (! empty($_GET['depot'])) { $p['depot'] = \App\Vraagbaak\Depots::herken(' ' . $_GET['depot'] . ' '); }
        $t0 = microtime(true);
        try {
            $r = $v->bereken($brug, $p);
            $ok++;
            printf("  OK   %-26s %5d ms  %s\n", $v->id, (microtime(true) - $t0) * 1000, $r->type === 'getal' ? $r->waarde . ' ' . $r->eenheid : ($r->type === 'tabel' ? count($r->rijen) . ' rijen' : mb_substr((string) $r->waarde, 0, 60)));
        } catch (\Throwable $e) {
            $fout++;
            printf("  FOUT %-26s %s\n", $v->id, mb_substr($e->getMessage(), 0, 160));
        }
    }
    echo "\nOK: $ok, FOUT: $fout\n";
}

// ?render=1 → de toolpagina en de catalogus-API server-side renderen als de eerste super-admin (alleen diagnose, geen sessie)
if (! empty($_GET['render'])) {
    echo "\nRender-test:\n";
    try {
        $u = \App\Models\User::where('is_super_admin', true)->orderBy('id')->first();
        auth()->setUser($u);
        $html = view('tools.vraagbaak')->render();
        echo "  view: " . strlen($html) . " tekens, titel aanwezig: " . (str_contains($html, 'Boels Vraagbaak') ? 'ja' : 'NEE') . "\n";
        $req = \Illuminate\Http\Request::create('/tools/vraagbaak/api?action=catalogus', 'GET');
        $req->setUserResolver(fn () => $u);
        $res = app(\App\Http\Controllers\Tools\VraagbaakController::class)->api($req);
        $json = is_array($res) ? $res : $res->getData(true);
        echo "  catalogus: " . count($json['bronnen'] ?? []) . " bronnen, manager=" . var_export($json['manager'] ?? null, true) . "\n";
        $req = \Illuminate\Http\Request::create('/tools/vraagbaak/api?action=vraag', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['vraag' => 'hoeveel liter heeft rotterdam getankt deze maand']));
        $req->setUserResolver(fn () => $u);
        $res = app(\App\Http\Controllers\Tools\VraagbaakController::class)->api($req);
        $json = is_array($res) ? $res : $res->getData(true);
        echo "  vraag: status=" . ($json['status'] ?? '?') . " waarde=" . json_encode($json['resultaat']['waarde'] ?? $json['melding'] ?? null) . "\n";
    } catch (\Throwable $e) {
        echo "  FOUT: " . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
    }
}
