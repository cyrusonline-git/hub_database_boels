<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);

        // Sanctum: behandel SPA-requests vanuit stateful domeinen als session-based.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 419 bij inloggen (verlopen of in de browser teruggezet inlogscherm): geen foutpagina, maar terug naar
        // het inlogscherm met het e-mailadres ingevuld en een vers token (Wim 19-11-2026).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && $request->isMethod('post') && $request->is('login')) {
                return redirect('/login')->withInput($request->only('email', 'remember'))
                    ->with('status', 'Je inlogscherm was verlopen. Log nog een keer in.');
            }

            return null;
        });
    })->create();
