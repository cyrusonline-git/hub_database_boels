<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::share('boelsBrand', config('boels.brand'));

        // Inloggen, uitloggen en mislukte pogingen vastleggen in de audit-log (met IP)
        $log = function (string $event, ?User $user, array $extra = []): void {
            try {
                AuditLog::create([
                    'user_id' => $user?->id,
                    'auditable_id' => $user?->id ?? 0,
                    'auditable_type' => User::class,
                    'event' => $event,
                    'old_values' => null,
                    'new_values' => $extra,
                    'ip_address' => request()?->ip(),
                    'user_agent' => substr((string) request()?->userAgent(), 0, 500),
                ]);
            } catch (\Throwable $e) {
                report($e); // een logfout mag het inloggen nooit blokkeren
            }
        };
        Event::listen(Login::class, fn (Login $e) => $log('login', $e->user instanceof User ? $e->user : null, ['email' => $e->user->email ?? null]));
        Event::listen(Logout::class, fn (Logout $e) => $log('logout', $e->user instanceof User ? $e->user : null));
        Event::listen(Failed::class, fn (Failed $e) => $log('login_failed', $e->user instanceof User ? $e->user : null, ['email' => $e->credentials['email'] ?? null]));
    }
}
