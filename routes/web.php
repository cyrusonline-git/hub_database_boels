<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\EmployeeController;
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
        // Redirect /admin/{resource}/{id}  -> /admin/{resource}/{id}/edit
        // (sommige links sturen naar /show terwijl we alleen edit hebben)
        foreach (['users','roles','permissions','applications','custom-fields','field-aliases'] as $r) {
            Route::get("$r/{id}", fn ($id) => redirect("/admin/$r/$id/edit"))->whereNumber('id');
        }

        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);
        Route::resource('applications', ApplicationController::class)->except(['show']);
        Route::resource('custom-fields', CustomFieldController::class)->except(['show']);
        Route::resource('field-aliases', FieldAliasController::class)->except(['show']);

        // Employees beheer
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');

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
