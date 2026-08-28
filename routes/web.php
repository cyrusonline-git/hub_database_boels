<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FieldAliasController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\InfrastructureController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\MaterialImportController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TableOwnershipController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ChatController;
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
// GET-variant voor de "Volledig uitloggen bij Boels CORE"-knop in
// child-apps (offertes, inhuur, ...) — die kunnen geen CSRF-POST doen.
Route::get('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout.get');

// Authenticated zone
Route::middleware('auth')->group(function () {
    Route::get('/launcher', [LauncherController::class, 'index'])->name('launcher');
    Route::get('/launcher/zoek', [LauncherController::class, 'search'])->middleware('throttle:60,1')->name('launcher.search');
    Route::get('/launcher/badges', [LauncherController::class, 'badges'])->middleware('throttle:60,1')->name('launcher.badges');

    // Rekentools — voor iedereen
    Route::view('/tools/generator', 'tools.generator')->name('tools.generator');
    Route::view('/tools/kabel', 'tools.kabel')->name('tools.kabel');
    Route::view('/tools/verlichting', 'tools.verlichting')->name('tools.verlichting');
    Route::view('/tools/transport', 'tools.transport')->name('tools.transport');

    // Klant-detail (alleen-lezen) — voor alle ingelogde medewerkers,
    // o.a. klikbaar vanuit de dashboard-zoeker
    Route::get('/klant/{customer}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.view');

    // Artikelen zoeken + bekijken (specs uit de subgroeplijst) — voor iedereen
    Route::get('/artikelen', [\App\Http\Controllers\ArticleController::class, 'index'])->name('articles.index');
    Route::get('/artikel/subgroep/{subgroup}', [\App\Http\Controllers\ArticleController::class, 'subgroup'])->name('articles.subgroup');
    Route::get('/artikel/{machine}', [\App\Http\Controllers\ArticleController::class, 'show'])->name('articles.show');

    // Eigen wachtwoord wijzigen (alle ingelogde medewerkers)
    Route::get('/wachtwoord-wijzigen', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/wachtwoord-wijzigen', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'update'])->middleware('throttle:10,1')->name('password.change.update');

    // Interne chat (alle ingelogde Boels-medewerkers)
    Route::get('/chat/unread', [ChatController::class, 'unread'])->name('chat.unread');
    Route::get('/chat/contacts', [ChatController::class, 'contacts'])->name('chat.contacts');
    Route::get('/chat/thread/{user}', [ChatController::class, 'thread'])->name('chat.thread');
    Route::post('/chat/send', [ChatController::class, 'send'])->middleware('throttle:60,1')->name('chat.send');
    Route::get('/chat/image/{message}', [ChatController::class, 'image'])->name('chat.image');
    Route::post('/chat/delete/{message}', [ChatController::class, 'destroy'])->name('chat.delete');

    // Super Admin / system management
    Route::middleware('role:super-admin,administrator')->prefix('admin')->name('admin.')->group(function () {
        // Redirect /admin/{resource}/{id}  -> /admin/{resource}/{id}/edit
        // (sommige links sturen naar /show terwijl we alleen edit hebben)
        foreach (['users','roles','permissions','applications','custom-fields','field-aliases'] as $r) {
            Route::get("$r/{id}", fn ($id) => redirect("/admin/$r/$id/edit"))->whereNumber('id');
        }

        Route::post('users/mail-wachtenden', [UserController::class, 'mailPending'])->name('users.mail-pending');
        Route::post('users/{user}/inlogmail', [UserController::class, 'sendLoginMail'])->name('users.send-login-mail');
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);
        Route::resource('applications', ApplicationController::class)->except(['show']);
        Route::post('applications/{application}/import-roles', [ApplicationController::class, 'importRoles'])->name('applications.import-roles');
        Route::post('applications/{application}/import-users', [ApplicationController::class, 'importUsersAction'])->name('applications.import-users');
        Route::post('applications/register-from-url', [ApplicationController::class, 'registerFromUrl'])->name('applications.register-from-url');
        Route::resource('custom-fields', CustomFieldController::class)->except(['show']);
        Route::resource('field-aliases', FieldAliasController::class)->except(['show']);

        // Employees beheer
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('employees/bulk-grant-login', [EmployeeController::class, 'bulkGrantLogin'])->name('employees.bulk-grant-login');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');

        // Infrastructuur: business unit > area > depot
        Route::get('infrastructuur', [InfrastructureController::class, 'index'])->name('infrastructure.index');
        Route::post('infrastructuur/sync', [InfrastructureController::class, 'syncFromEmployees'])->name('infrastructure.sync');
        Route::post('infrastructuur/units', [InfrastructureController::class, 'storeUnit'])->name('infrastructure.units.store');
        Route::delete('infrastructuur/units/{unit}', [InfrastructureController::class, 'destroyUnit'])->name('infrastructure.units.destroy');
        Route::post('infrastructuur/areas', [InfrastructureController::class, 'storeArea'])->name('infrastructure.areas.store');
        Route::put('infrastructuur/areas/{area}', [InfrastructureController::class, 'updateArea'])->name('infrastructure.areas.update');
        Route::delete('infrastructuur/areas/{area}', [InfrastructureController::class, 'destroyArea'])->name('infrastructure.areas.destroy');
        Route::post('infrastructuur/depots', [InfrastructureController::class, 'storeDepot'])->name('infrastructure.depots.store');
        Route::put('infrastructuur/depots/{depot}', [InfrastructureController::class, 'updateDepot'])->name('infrastructure.depots.update');
        Route::delete('infrastructuur/depots/{depot}', [InfrastructureController::class, 'destroyDepot'])->name('infrastructure.depots.destroy');

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

        // Handige links op het dashboard (rekentools, documenten, sites)
        Route::get('handige-links', [\App\Http\Controllers\Admin\QuickLinkController::class, 'index'])->name('quick-links.index');
        Route::post('handige-links', [\App\Http\Controllers\Admin\QuickLinkController::class, 'store'])->name('quick-links.store');
        Route::put('handige-links/{quickLink}', [\App\Http\Controllers\Admin\QuickLinkController::class, 'update'])->name('quick-links.update');
        Route::delete('handige-links/{quickLink}', [\App\Http\Controllers\Admin\QuickLinkController::class, 'destroy'])->name('quick-links.destroy');

        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('table-ownership', [TableOwnershipController::class, 'index'])->name('table-ownership.index');
    });
});
