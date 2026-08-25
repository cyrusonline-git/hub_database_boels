<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppBadge;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Notificatie-tellers voor de dashboard-tegels. Child-apps melden het
 * absolute aantal openstaande items per medewerker; de app bepaalt zelf
 * wat "nieuw" is en 0 melden wist het bolletje.
 *
 * Twee ingangen:
 * - POST /api/badge           — met de sync_key van de app (elke server)
 * - POST /api/internal/badge  — zonder sleutel, alleen vanaf de eigen
 *   server (zelfde conventie als core-users.php in de apps)
 */
class BadgeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'app' => ['required', 'string', 'max:100'],
            'k' => ['required', 'string', 'max:64'],
        ]);

        $app = Application::where('slug', $request->input('app'))->whereNotNull('sync_key')->first();
        abort_unless($app && hash_equals($app->sync_key, (string) $request->input('k')), 403, 'Onbekende app of verkeerde sleutel.');

        return $this->apply($app, $request);
    }

    public function storeInternal(Request $request)
    {
        $eigen = [$request->server('SERVER_ADDR'), '127.0.0.1', '::1'];
        abort_unless(in_array($request->ip(), array_filter($eigen), true), 403);

        $request->validate(['app' => ['required', 'string', 'max:100']]);
        $app = Application::where('slug', $request->input('app'))->first();
        abort_unless($app, 404, 'Onbekende applicatie.');

        return $this->apply($app, $request);
    }

    private function apply(Application $app, Request $request)
    {
        $data = $request->validate([
            'email' => ['required_without:items', 'email'],
            'count' => ['required_without:items', 'integer', 'min:0', 'max:9999'],
            'items' => ['sometimes', 'array', 'max:500'],
            'items.*.email' => ['required', 'email'],
            'items.*.count' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $items = $data['items'] ?? [['email' => $data['email'], 'count' => $data['count']]];
        $updated = 0;
        $unknown = [];

        foreach ($items as $item) {
            $user = User::where('email', $item['email'])->first();
            if (! $user) {
                $unknown[] = $item['email'];
                continue;
            }
            AppBadge::updateOrCreate(
                ['application_id' => $app->id, 'user_id' => $user->id],
                ['count' => $item['count']],
            );
            $updated++;
        }

        return ['ok' => true, 'updated' => $updated, 'unknown_emails' => $unknown];
    }
}
