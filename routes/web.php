<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FieldAliasController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\MaterialImportController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TableOwnershipController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\LauncherController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/launcher');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/activate/{token}', [ActivationController::class, 'show'])->name('activate.show');
    Route::post('/activate/{token}', [ActivationController::class, 'activate'])->middleware('throttle:10,1')->name('activate.do');

    // Wachtwoord vergeten / reset
    Route::get('/wachtwoord-vergeten', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/wachtwoord-vergeten', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/wachtwoord-reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/wachtwoord-reset', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
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
        Route::post('applications/{application}/import-roles', [ApplicationController::class, 'importRoles'])->name('applications.import-roles');
        Route::resource('custom-fields', CustomFieldController::class)->except(['show']);
        Route::resource('field-aliases', FieldAliasController::class)->except(['show']);

        // Employees beheer
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('employees/bulk-grant-login', [EmployeeController::class, 'bulkGrantLogin'])->name('employees.bulk-grant-login');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');

        // Klanten (Industrial) — lijst + upload + detail
        Route::get('klanten', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('klanten/upload', [CustomerController::class, 'upload'])->name('customers.upload');
        Route::get('klanten/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        // Materieel bekijken — doorklikbaar: analysegroep > productgroep > subgroep > machine
        Route::get('materieel', [MaterialController::class, 'index'])->name('material.index');
        Route::get('materieel/productgroepen', [MaterialController::class, 'groups'])->name('material.groups');
        Route::get('materieel/subgroepen', [MaterialController::class, 'subgroups'])->name('material.subgroups');
        Route::get('materieel/machines', [MaterialController::class, 'machines'])->name('material.machines');
        Route::get('materieel/subgroep/{subgroup}', [MaterialController::class, 'show'])->name('material.show');
        Route::get('materieel/machine/{machine}', [MaterialController::class, 'machine'])->name('material.machine');

        // Materieellijst uploads (subgroepen + unieke nummers)
        Route::get('material-imports', [MaterialImportController::class, 'index'])->name('material-imports.index');
        Route::post('material-imports/subgroups', [MaterialImportController::class, 'uploadSubgroups'])->name('material-imports.subgroups');
        Route::post('material-imports/machines', [MaterialImportController::class, 'uploadMachines'])->name('material-imports.machines');

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
