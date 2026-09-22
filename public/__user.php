<?php
/**
 * Boels CORE — gebruiker/medewerker opzoeken (alleen-lezen, blijft staan).
 * Open: https://databasehub.sorai.nl/__user.php?k=<DEPLOY_SECRET>&q=<naam of e-mail>
 * Toont wat child-apps via /api/me krijgen: allowed_depots/areas van het ACCOUNT,
 * met terugval op de medewerkerkaart, plus rollen per app.
 */
$secret = null;
foreach ([__DIR__ . '/../laravel_app/.env', __DIR__ . '/../.env'] as $envFile) {
    if (file_exists($envFile) && preg_match('/^DEPLOY_SECRET=(.+)$/m', file_get_contents($envFile), $m)) { $secret = trim($m[1]); break; }
}
if ($secret === null || ! hash_equals($secret, (string) ($_GET['k'] ?? ''))) { http_response_code(403); exit('forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
$root = null;
foreach ([__DIR__ . '/../laravel_app', __DIR__ . '/..'] as $p) { if (file_exists($p . '/vendor/autoload.php')) { $root = realpath($p); break; } }
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '') exit("Geef ?q=<naam of e-mail>\n");
$users = \App\Models\User::with(['employee', 'roles'])->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))->limit(10)->get();
if ($users->isEmpty()) echo "Geen gebruikersaccount gevonden voor '$q'.\n";
foreach ($users as $u) {
    echo "ACCOUNT #{$u->id} {$u->name} <{$u->email}> status={$u->status} super_admin=" . ($u->is_super_admin ? 'ja' : 'nee') . "\n";
    echo "  account allowed_depots: " . json_encode($u->allowed_depots) . "\n";
    echo "  account allowed_areas:  " . json_encode($u->allowed_areas) . "\n";
    echo "  medewerkerkaart:        " . ($u->employee ? "#{$u->employee->id} depot='{$u->employee->depot}' area='{$u->employee->area}'" : 'geen') . "\n";
    $depots = $u->allowed_depots ?: ($u->employee?->depot ? [$u->employee->depot] : []);
    $areas = $u->allowed_areas ?: ($u->employee?->area ? [$u->employee->area] : []);
    echo "  → /api/me geeft:        depots=" . json_encode($depots) . " areas=" . json_encode($areas) . "\n";
    echo "  rollen: " . $u->roles->map(fn ($r) => $r->slug . ($r->application_id ? " (app {$r->application_id})" : ''))->implode(', ') . "\n\n";
}
$emps = \App\Models\Employee::where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))->limit(10)->get();
foreach ($emps as $e) echo "MEDEWERKERKAART #{$e->id} {$e->name} <{$e->email}> depot='{$e->depot}' area='{$e->area}'\n";
