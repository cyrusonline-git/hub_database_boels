<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\FieldAliasController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TableOwnershipController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LauncherController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/launcher');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Authenticated zone
Route::middleware('auth')->group(function () {
    Route::get('/launcher', [LauncherController::class, 'index'])->name('launcher');

    // Super Admin / system management
    Route::middleware('role:super-admin,administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('applications', ApplicationController::class);
        Route::resource('custom-fields', CustomFieldController::class);
        Route::resource('field-aliases', FieldAliasController::class);

        Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
        Route::get('imports/create', [ImportController::class, 'create'])->name('imports.create');
        Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
        Route::get('imports/{importJob}/mapping', [ImportController::class, 'mapping'])->name('imports.mapping');
        Route::post('imports/{importJob}/mapping', [ImportController::class, 'storeMapping'])->name('imports.storeMapping');
        Route::post('imports/{importJob}/run', [ImportController::class, 'run'])->name('imports.run');
        Route::get('imports/{importJob}', [ImportController::class, 'show'])->name('imports.show');

        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('table-ownership', [TableOwnershipController::class, 'index'])->name('table-ownership.index');
    });
});
