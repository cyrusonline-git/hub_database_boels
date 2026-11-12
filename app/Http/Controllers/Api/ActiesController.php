<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Acties\ActiesService;
use Illuminate\Http\Request;

class ActiesController extends Controller
{
    public function __construct(private ActiesService $acties) {}

    /** Eigen open werk (ingelogde CORE-gebruiker). */
    public function mijn(Request $r)
    {
        $u = $r->user();
        $items = $this->acties->voorPersoon((string) $u->email, $u->employee_id);

        return ['email' => $u->email, 'totaal' => array_sum(array_column($items, 'aantal')), 'items' => $items];
    }

    /** Alle personen — alleen super-admins en managers/beheerders van de Dashboard-app. */
    public function alle(Request $r)
    {
        $u = $r->user();
        if (! $u->is_super_admin) {
            $appId = Application::where('slug', 'dashboard')->value('id');
            $mag = $appId && $u->roles()->where('application_id', $appId)->whereIn('slug', ['area-manager', 'bu-manager', 'admin'])->exists();
            abort_unless($mag, 403, 'Alleen voor managers/beheerders van het Dashboard.');
        }
        $uit = [];
        foreach ($this->acties->overzicht() as $email => $items) {
            $uit[] = ['email' => $email, 'totaal' => array_sum(array_column($items, 'aantal')), 'items' => array_values($items)];
        }

        return ['data' => $uit];
    }
}
