<?php
/**
 * Tijdelijk: toont de vestiging/area/depot-gegevens van een CORE-gebruiker,
 * om een SSO-koppelingsprobleem met een child-app (bv. Tankapp) te diagnosticeren.
 * Open: https://databasehub.sorai.nl/__diag-user.php?k=<DEPLOY_SECRET>&q=rene
 * Verwijderen na gebruik.
 */
$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) { $secret = trim($m[1]); break; }
}
if ($secret === null || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$root = null;
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) { if (file_exists($p . '/vendor/autoload.php')) { $root = realpath($p); break; } }
if (! $root) exit("FOUT: Laravel niet gevonden.\n");
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '') exit("Geef ?q=<naam of e-mail> mee.\n");

$users = DB::table('users')
    ->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")
    ->get(['id', 'name', 'email', 'allowed_areas', 'allowed_depots', 'allowed_countries']);

if ($users->isEmpty()) { exit("Geen gebruiker gevonden voor '$q'.\n"); }

foreach ($users as $u) {
    echo "=== {$u->name}  <{$u->email}>  (id {$u->id}) ===\n";
    echo "allowed_areas:     " . $u->allowed_areas . "\n";
    echo "allowed_depots:    " . $u->allowed_depots . "\n";
    echo "allowed_countries: " . $u->allowed_countries . "\n";
    // Rollen erbij
    $rollen = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('model_has_roles.model_id', $u->id)->pluck('roles.slug')->all();
    echo "rollen:            " . implode(', ', $rollen) . "\n\n";
}

// Toon ook de bekende areas/depots in CORE (org_areas / org_depots) voor Belgische vergelijking.
echo "=== CORE org-areas (met land) ===\n";
foreach (DB::table('org_areas')->get(['id', 'name']) as $a) echo "  #{$a->id}  {$a->name}\n";
echo "\n=== CORE org-depots die 'antwerp' of 'belg' bevatten ===\n";
foreach (DB::table('org_depots')->where('name', 'like', '%antwerp%')->orWhere('name', 'like', '%belg%')->get(['id', 'name']) as $d) echo "  #{$d->id}  {$d->name}\n";

if (($_GET['weg'] ?? '') === '1') { @unlink(__FILE__); echo "\n(diag verwijderd)\n"; }
