<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Boels CORE als identity provider.
// Child-apps gebruiken deze endpoints om user-info + rechten op te halen.
// Beschikbaar via sessie-cookie (cross-subdomein .sorai.nl) OF Sanctum tokens.

Route::middleware('auth:sanctum')->group(function () {

    // Volledig profiel — child-apps cachen dit per request
    Route::get('/me', function (Request $request) {
        $u = $request->user();
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'is_super_admin' => $u->is_super_admin,
            'status' => $u->status,
            'employee_id' => $u->employee_id,
            'allowed_areas' => $u->allowed_areas ?? [],
            'allowed_depots' => $u->allowed_depots ?? [],
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
