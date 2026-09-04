<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Boels CORE als identity provider.
// Child-apps gebruiken deze endpoints om user-info + rechten op te halen.
// Beschikbaar via sessie-cookie (cross-subdomein .sorai.nl) OF Sanctum tokens.

// Interne data-toegang voor child-apps op dezelfde server (geen gebruikers-
// sessie nodig — bv. klanten zoeken terwijl iemand lokaal is ingelogd in de
// app). Alleen bereikbaar vanaf de eigen server, net als de core-users.php
// conventie in de apps.
Route::get('/internal/customers', function (Request $request) {
    $eigen = [$request->server('SERVER_ADDR'), '127.0.0.1', '::1'];
    abort_unless(in_array($request->ip(), array_filter($eigen), true), 403);
    return app(\App\Http\Controllers\Api\DataController::class)->customers($request);
});

// Materieellijst (producttypes/subgroepen) voor child-apps op dezelfde
// server — o.a. de artikel-zoeker van de offerte-app. Zelfde IP-guard.
Route::get('/internal/subgroups', function (Request $request) {
    $eigen = [$request->server('SERVER_ADDR'), '127.0.0.1', '::1'];
    abort_unless(in_array($request->ip(), array_filter($eigen), true), 403);

    $q = trim((string) $request->input('q'));
    if (mb_strlen($q) < 2) {
        return [];
    }
    $like = '%'.$q.'%';

    return \App\Models\MachineSubgroup::query()
        ->where(fn ($w) => $w
            ->where('subgroup_number', 'like', $like)
            ->orWhere('subgroup_name', 'like', $like)
            ->orWhere('merk', 'like', $like)
            ->orWhere('type', 'like', $like))
        ->orderBy('subgroup_name')
        ->limit(intval($request->input('limit', 20)))
        ->get(['subgroup_number', 'subgroup_name', 'merk', 'type']);
});

// Notificatie-tellers voor de dashboard-tegels. Child-apps melden hier
// (server-to-server, met hun eigen sync_key uit CORE Admin > Applicaties)
// het absolute aantal openstaande items per medewerker. De app bepaalt
// zelf wat "nieuw" is; 0 melden haalt het bolletje weg.
//
//   POST /api/badge  { app: "tankapp", k: "<sync_key>", email: "x@boels.nl", count: 1 }
//   of in bulk:      { app, k, items: [ { email, count }, ... ] }
Route::post('/badge', [\App\Http\Controllers\Api\BadgeController::class, 'store'])->middleware('throttle:120,1');
Route::post('/internal/badge', [\App\Http\Controllers\Api\BadgeController::class, 'storeInternal'])->middleware('throttle:120,1');

Route::middleware('auth:sanctum')->group(function () {

    // Volledig profiel — child-apps cachen dit per request
    Route::get('/me', function (Request $request) {
        $u = $request->user();

        // Vangnet: staan er geen toegangslijsten op het login-account
        // (super-admins bijvoorbeeld), gebruik dan het depot/de area van
        // de gekoppelde MEDEWERKERKAART — zo krijgen child-apps altijd
        // een thuisdepot mee voor dashboards en nieuwe aanvragen.
        $areas = $u->allowed_areas ?? [];
        $depots = $u->allowed_depots ?? [];
        if (empty($areas) && $u->employee?->area) {
            $areas = [$u->employee->area];
        }
        if (empty($depots) && $u->employee?->depot) {
            $depots = [$u->employee->depot];
        }

        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'is_super_admin' => $u->is_super_admin,
            'status' => $u->status,
            'employee_id' => $u->employee_id,
            'allowed_areas' => $areas,
            'allowed_depots' => $depots,
            'allowed_countries' => $u->allowed_countries ?? [],
            'roles' => $u->roles->pluck('slug'),
            'permissions' => $u->permissions()->pluck('key'),
        ];
    });

    // Lijst applicaties waar deze user in mag (al area-gefilterd)
    Route::get('/applications', function (Request $request) {
        return $request->user()->applications()->values();
    });

    // Snelle permissie-check voor child-apps
    Route::get('/can/{permission}', function (Request $request, string $permission) {
        return ['allowed' => $request->user()->hasPermission($permission)];
    });

    // Rollen + permissies van de ingelogde user BINNEN één app.
    // Child-apps regelen hun eigen rollen: maak in CORE Admin rollen aan
    // met die app als applicatie, en vraag ze hier op.
    Route::get('/access/{appSlug}', function (Request $request, string $appSlug) {
        $app = \App\Models\Application::where('slug', $appSlug)->first();
        abort_unless($app, 404, 'Onbekende applicatie.');
        $u = $request->user();

        $roles = $u->roles()
            ->where(fn ($q) => $q->whereNull('application_id')->orWhere('application_id', $app->id))
            ->get(['roles.id', 'roles.name', 'roles.slug', 'roles.application_id']);

        $permissions = $u->is_super_admin
            ? \App\Models\Permission::where(fn ($q) => $q->whereNull('application_id')->orWhere('application_id', $app->id))->pluck('key')
            : $u->permissions()
                ->where(fn ($q) => $q->whereNull('application_id')->orWhere('application_id', $app->id))
                ->pluck('key');

        return [
            'app' => $app->slug,
            'is_super_admin' => $u->is_super_admin,
            'roles' => $roles->map(fn ($r) => [
                'slug' => $r->slug,
                'name' => $r->name,
                'scope' => $r->application_id ? 'app' : 'platform',
            ]),
            'permissions' => $permissions,
        ];
    });

    // Alle CORE-gebruikers die een rol hebben BINNEN één app (plus de
    // super-admins), zodat een child-app zijn toegangsbeheer vooraf kan
    // vullen/spiegelen vanuit CORE — CORE is leidend. Alleen op te vragen
    // door een super-admin of een app-beheerder (rol admin/superadmin in
    // die app). Geen wachtwoorden, geen permissies.
    Route::get('/app-users/{appSlug}', function (Request $request, string $appSlug) {
        $app = \App\Models\Application::where('slug', $appSlug)->first();
        abort_unless($app, 404, 'Onbekende applicatie.');
        $u = $request->user();

        $isAppBeheerder = $u->roles()
            ->where('application_id', $app->id)
            ->whereIn('slug', ['admin', 'superadmin', 'super-admin'])
            ->exists();
        abort_unless($u->is_super_admin || $isAppBeheerder, 403, 'Alleen voor beheerders van deze app.');

        $users = \App\Models\User::query()
            ->where('status', '!=', \App\Models\User::STATUS_DISABLED)
            ->where(function ($q) use ($app) {
                $q->where('is_super_admin', true)
                  ->orWhereIn('id', function ($sub) use ($app) {
                      $sub->select('user_id')->from('user_roles')
                          ->whereIn('role_id', function ($r) use ($app) {
                              $r->select('id')->from('roles')
                                ->where('application_id', $app->id)
                                ->whereNull('deleted_at');
                          });
                  });
            })
            ->with(['roles' => fn ($q) => $q->where('application_id', $app->id)])
            ->orderBy('name')
            ->get();

        return [
            'app' => $app->slug,
            'users' => $users->map(fn ($x) => [
                'id' => $x->id,
                'name' => $x->name,
                'email' => $x->email,
                'status' => $x->status,
                'is_super_admin' => (bool) $x->is_super_admin,
                'roles' => $x->roles->pluck('slug')->values(),
            ])->values(),
        ];
    });

    // Organisatiestructuur: business unit > area > depot — voor alle apps
    Route::get('/infrastructure', function () {
        return \App\Models\BusinessUnit::with('areas.depots')
            ->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn ($u) => [
                'name' => $u->name,
                'areas' => $u->areas->map(fn ($a) => [
                    'name' => $a->name,
                    'country' => $a->country,
                    'depots' => $a->depots->map(fn ($d) => [
                        'name' => $d->name,
                        'email' => $d->email,
                        'city' => $d->city,
                    ])->values(),
                ])->values(),
            ]);
    });

    // ---- Read-only data-API: klanten, materieel, personeel ----
    Route::get('/customers', [\App\Http\Controllers\Api\DataController::class, 'customers']);
    Route::get('/customers/{number}', [\App\Http\Controllers\Api\DataController::class, 'customer']);
    Route::get('/machines', [\App\Http\Controllers\Api\DataController::class, 'machines']);
    Route::get('/machines/{number}', [\App\Http\Controllers\Api\DataController::class, 'machine']);
    Route::get('/subgroups/{number}', [\App\Http\Controllers\Api\DataController::class, 'subgroup']);
    Route::get('/employees', [\App\Http\Controllers\Api\DataController::class, 'employees']);
});
