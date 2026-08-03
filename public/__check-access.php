<?php
/**
 * Boels CORE — Diagnose rollen/toegang (alleen-lezen).
 * Sleutel = DEPLOY_SECRET. Verwijdert zichzelf na gebruik.
 */

$envFile = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $p) {
    if (file_exists($p)) { $envFile = realpath($p); break; }
}
if (! $envFile) exit('env niet gevonden');
$contents = file_get_contents($envFile);
if (! preg_match('/^DEPLOY_SECRET=(.+)$/m', $contents, $m)
    || ! hash_equals(trim($m[1]), (string) ($_GET['k'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) {
    if (file_exists($p . '/vendor/autoload.php')) {
        require $p . '/vendor/autoload.php';
        $app = require_once $p . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        break;
    }
}

use Illuminate\Support\Facades\DB;

echo "Applicaties (id | slug | naam):\n";
foreach (DB::table('applications')->get() as $a) {
    printf("  #%-3d %-20s %-30s %s %s\n", $a->id, $a->slug, $a->name,
        $a->active ? 'actief' : 'INACTIEF', $a->deleted_at ? '[VERWIJDERD]' : '');
}

echo "\nRollen (id | slug | app_id):\n";
foreach (DB::table('roles')->get() as $r) {
    printf("  #%-3d %-22s app=%-4s %-25s %s\n", $r->id, $r->slug,
        $r->application_id ?? '-', $r->name, $r->deleted_at ? '[VERWIJDERD]' : '');
}

echo "\nGebruikers en hun rollen:\n";
foreach (DB::table('users')->whereNull('deleted_at')->get(['id', 'name', 'email', 'is_super_admin', 'active', 'status']) as $u) {
    printf("  #%-3d %-32s %s%s [%s]\n", $u->id, $u->email, $u->name,
        $u->is_super_admin ? ' *** SUPER-ADMIN ***' : '', $u->status);
    $roles = DB::table('user_roles')
        ->join('roles', 'roles.id', '=', 'user_roles.role_id')
        ->where('user_roles.user_id', $u->id)
        ->get(['roles.slug', 'roles.application_id', 'roles.deleted_at']);
    foreach ($roles as $r) {
        printf("        rol: %-22s app=%-4s %s\n", $r->slug,
            $r->application_id ?? 'platform', $r->deleted_at ? '[ROL VERWIJDERD]' : '');
    }
    if ($roles->isEmpty()) echo "        (geen rollen)\n";
}

@unlink(__FILE__);
echo "\nKlaar — script heeft zichzelf verwijderd.\n";
