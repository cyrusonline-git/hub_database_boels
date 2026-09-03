<?php
/**
 * Boels CORE — controle van app-slugs (alleen-lezen).
 *
 * Open: https://databasehub.sorai.nl/__apps.php?k=<DEPLOY_SECRET>
 * Toont per applicatie de slug waarop child-apps /api/access/{slug}
 * opvragen, de URL, actief/verwijderd en de app-rollen. Een lege of
 * afwijkende slug betekent: die app krijgt 404 "Onbekende applicatie" en
 * dus géén rollen uit CORE.
 */

$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) {
        $secret = trim($m[1]);
        break;
    }
}
if ($secret === null || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$root = null;
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        $root = realpath($p);
        break;
    }
}
if (! $root) exit("FOUT: Laravel niet gevonden.\n");

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Slug die elke child-app zelf gebruikt (SSO_APP_SLUG in de app-code)
$verwacht = [
    'projectschade' => 'Boels Projectschade',
    'inhuur'        => 'Inhuur (boels.sorai.nl)',
    'offertes'      => 'Offerte-app (accountmanagers)',
    'tankapp'       => 'Tankapp',
    'scanner'       => 'Scanner',
    'bulkretour'    => 'Bulkretour (alleen CORE-login)',
    'damen'         => 'Damen klantportaal',
    'rmw'           => 'Rotterdam Marine Works klantportaal',
    'bp'            => 'BP klantportaal',
];

echo "Boels CORE — applicaties en slugs (" . date('Y-m-d H:i') . ")\n" . str_repeat('=', 70) . "\n\n";

$apps = \Illuminate\Support\Facades\DB::table('applications')->orderBy('sort_order')->orderBy('id')->get();
$rollen = \Illuminate\Support\Facades\DB::table('roles')->whereNull('deleted_at')->whereNotNull('application_id')
    ->get(['application_id', 'slug'])->groupBy('application_id');

$gezien = [];
foreach ($apps as $a) {
    $status = $a->deleted_at ? 'VERWIJDERD' : ($a->active ? 'actief' : 'inactief');
    $slug = (string) $a->slug;
    $gezien[$slug] = true;
    $opm = '';
    if ($slug === '') {
        $opm = '  <-- LEGE SLUG: child-app krijgt 404';
    } elseif (! isset($verwacht[$slug])) {
        $opm = '  <-- geen child-app bekend met deze slug (klopt voor tools/portalen zonder rollenkoppeling)';
    }
    printf("#%-3d %-10s slug=%-22s naam=%s\n     url=%s\n     rollen=%s%s\n\n",
        $a->id, $status, "'$slug'", $a->name, $a->url,
        implode(', ', ($rollen[$a->id] ?? collect())->pluck('slug')->all()) ?: '-', $opm);
}

echo str_repeat('-', 70) . "\nVerwachte slugs (uit de app-code):\n";
foreach ($verwacht as $slug => $naam) {
    $ok = isset($gezien[$slug]) ? 'OK ' : 'ONTBREEKT';
    printf("  %-9s %-15s %s\n", $ok, $slug, $naam);
}
echo "\n";
