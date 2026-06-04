<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        if (! $user->hasRole($roles)) {
            abort(403, 'Geen toegang (rol ontbreekt).');
        }

        return $next($request);
    }
}
