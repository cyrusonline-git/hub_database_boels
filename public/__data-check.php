<?php
/**
 * Boels CORE — Materieel data-check (diagnose, alleen-lezen).
 * Open: https://databasehub.sorai.nl/__data-check.php?k=BOELS_DATA_2026
 */

$secret = 'BOELS_DATA_2026';
if (($_GET['k'] ?? '') !== $secret) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$candidates = [__DIR__ . '/../laravel_app', __DIR__ . '/..'];
$root = null;
foreach ($candidates as $p) {
    if (file_exists($p . '/vendor/autoload.php')) { $root = realpath($p); break; }
}
if (! $root) exit("FOUT: Laravel niet gevonden.\n");
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Boels CORE — Materieel data-check\n" . str_repeat('=', 60) . "\n\n";

echo "Totalen: "
    . DB::table('machine_groups')->whereNull('deleted_at')->count() . " groepen, "
    . DB::table('machine_subgroups')->whereNull('deleted_at')->count() . " subgroepen, "
    . DB::table('machines')->whereNull('deleted_at')->count() . " machines\n\n";

echo "Per analysegroep (subgroepen / machines):\n";
$rows = DB::table('machine_groups as g')
    ->leftJoin('machine_subgroups as sg', fn ($j) => $j->on('sg.group_id', '=', 'g.id')->whereNull('sg.deleted_at'))
    ->leftJoin('machines as m', fn ($j) => $j->on('m.subgroup_id', '=', 'sg.id')->whereNull('m.deleted_at'))
    ->whereNull('g.deleted_at')
    ->selectRaw("coalesce(g.analysis_group,'(GEEN)') as ag, count(distinct g.id) g, count(distinct sg.id) s, count(distinct m.id) m")
    ->groupBy('ag')->orderByDesc(DB::raw('count(distinct sg.id)'))->get();
foreach ($rows as $r) {
    printf("  %-40s %4d groepen %5d subgr %6d mach\n", mb_substr($r->ag, 0, 40), $r->g, $r->s, $r->m);
}

echo "\nGroepen met 'aggregaat' in de naam:\n";
foreach (DB::table('machine_groups')->where('group_name', 'like', '%aggregaat%')->get() as $g) {
    $sgc = DB::table('machine_subgroups')->where('group_id', $g->id)->whereNull('deleted_at')->count();
    printf("  #%-4d %-45s analyse=%-25s subgroepen=%d %s\n",
        $g->id, mb_substr($g->group_name, 0, 45), $g->analysis_group ?? '(GEEN)', $sgc,
        $g->deleted_at ? '[VERWIJDERD]' : '');
}

echo "\nVoorbeeldsubgroepen 84303 / 84305 / 84183 / 18084:\n";
foreach (['84303', '84305', '84183', '18084'] as $nr) {
    $sg = DB::table('machine_subgroups as sg')
        ->leftJoin('machine_groups as g', 'g.id', '=', 'sg.group_id')
        ->where('sg.subgroup_number', $nr)
        ->selectRaw("sg.subgroup_number, sg.subgroup_name, sg.deleted_at, sg.specifications is not null as has_specs,
                     g.group_name, g.analysis_group,
                     (select count(*) from machines m where m.subgroup_id = sg.id and m.deleted_at is null) as mc")
        ->first();
    if (! $sg) { echo "  $nr: NIET GEVONDEN\n"; continue; }
    printf("  %-6s → groep=%-40s analyse=%-22s specs=%s machines=%d %s\n    naam: %s\n",
        $sg->subgroup_number, mb_substr($sg->group_name ?? '(geen)', 0, 40),
        $sg->analysis_group ?? '(GEEN)', $sg->has_specs ? 'ja' : 'nee', $sg->mc,
        $sg->deleted_at ? '[VERWIJDERD]' : '', mb_substr($sg->subgroup_name, 0, 60));
}

echo "\nLaatste 5 groepen (nieuwst aangemaakt):\n";
foreach (DB::table('machine_groups')->orderByDesc('id')->limit(5)->get() as $g) {
    printf("  #%-4d %-45s analyse=%s\n", $g->id, mb_substr($g->group_name, 0, 45), $g->analysis_group ?? '(GEEN)');
}

echo "\nKlaar (alleen-lezen, niets gewijzigd).\n";
