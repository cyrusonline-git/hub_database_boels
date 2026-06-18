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
});
