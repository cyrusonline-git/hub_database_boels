<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/launcher';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Routing wordt al volledig geconfigureerd in bootstrap/app.php (Laravel 11 stijl).
        // Deze provider blijft bestaan voor de HOME-constante en eventuele toekomstige bindings.
    }
}
