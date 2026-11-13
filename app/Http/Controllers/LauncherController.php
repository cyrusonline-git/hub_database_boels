<?php

namespace App\Http\Controllers;

use App\Models\AppBadge;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\ImportJob;
use App\Models\Machine;
use App\Models\QuickLink;
use App\Models\User;
use Illuminate\Http\Request;

class LauncherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // applications() retourneert al een gefilterde Collection
        $apps = $user->applications();

        $isAdmin = $user->is_super_admin || $user->hasRole(['super-admin', 'administrator']);

        // Notificatie-bolletjes op de tegels (gemeld door de child-apps)
        $badges = AppBadge::where('user_id', $user->id)->where('count', '>', 0)
            ->pluck('count', 'application_id');

        // Handige links (rekentools, documenten) per categorie.
        // Regio-gebonden links (kolom area) zijn alleen zichtbaar voor
        // medewerkers van die area (uit de toegangslijst of de
        // medewerkerkaart); beheerders zien altijd alles.
        $userAreas = $user->allowed_areas ?? [];
        if (empty($userAreas) && $user->employee?->area) {
            $userAreas = [$user->employee->area];
        }
        $normArea = fn ($a) => trim(preg_replace('/^area\s+/', '', strtolower(trim((string) $a))));
        $userAreasNorm = array_map($normArea, $userAreas);

        $quickLinks = QuickLink::where('active', true)
            ->orderBy('sort_order')->orderBy('title')->get()
            ->filter(function ($l) use ($isAdmin, $userAreasNorm, $normArea) {
                if (! $l->area || $isAdmin) {
                    return true;
                }
                return in_array($normArea($l->area), $userAreasNorm, true);
            })
            ->groupBy(fn ($l) => $l->category ?: 'Overig');

        // Beheer-informatie alleen voor admins — gewone gebruikers zien
        // apps, zoeken en snelkoppelingen.
        $admin = null;
        if ($isAdmin) {
            $admin = [
                'stats' => [
                    'employees'     => Employee::where('active', true)->count(),
                    'customers'     => Customer::count(),
                    'machines'      => Machine::count(),
                    'users_active'  => User::where('status', User::STATUS_ACTIVE)->count(),
                    'users_pending' => User::where('status', User::STATUS_PENDING)->count(),
                ],
                'auditLogs' => AuditLog::with('user:id,name')
                    ->orderByDesc('created_at')->limit(6)->get(),
                'pendingUsers' => User::where('status', User::STATUS_PENDING)
                    ->orderByDesc('created_at')->limit(5)->get(['id', 'name', 'email', 'created_at']),
                'lastImport' => ImportJob::orderByDesc('created_at')->first(),
            ];
        }

        // Snelkoppelingen naar de onderdelen van het Dashboard (alleen als die app voor deze
        // gebruiker zichtbaar is: depot Rotterdam + dashboard-rol). Per rol de passende tegels.
        $dashboardTegels = [];
        $dash = $apps->firstWhere('slug', 'dashboard');
        if ($dash && $dash->url) {
            $base = rtrim($dash->url, '/');
            $rollen = $user->is_super_admin ? ['admin'] : $user->roles()->where('application_id', $dash->id)->pluck('slug')->map(fn ($s) => str_replace('-', '_', $s))->all();
            $heeft = fn (array $toegestaan) => $user->is_super_admin || array_intersect($rollen, $toegestaan);
            $tegels = [
                ['Dashboard', 'bi-speedometer2', '/', null, 'Mijn start: vandaag te leveren, op te halen, factuurrun', true],
                ['Planning', 'bi-calendar-check', '/planning', null, 'Orders per leverdag met pickstand', false],
                ['Planbord', 'bi-kanban', '/planbord', null, 'Aankomende orders op tijd', false],
                ['Uit factuurrun', 'bi-receipt', '/factuurrun', ['binnendienst', 'servicedesk', 'area_manager', 'bu_manager', 'admin'], 'Contracten die niet gefactureerd worden', false],
                ['Ophaallijst', 'bi-arrow-return-left', '/in-huur/ophalen', null, 'Afgemeld, nog op te halen', false],
                ['In huur', 'bi-box-seam', '/in-huur', null, 'Alles wat bij klanten staat', false],
                ['AVI', 'bi-cash-coin', '/in-huur/avi', ['binnendienst', 'area_manager', 'bu_manager', 'admin'], 'Weekhuur per klant en in de tijd', false],
                ['Acties per persoon', 'bi-people', '/team', ['area_manager', 'bu_manager', 'admin'], 'Open werk per medewerker', false],
            ];
            foreach ($tegels as [$label, $icoon, $pad, $rolFilter, $sub, $breed]) {
                if ($rolFilter === null || $heeft($rolFilter)) {
                    $dashboardTegels[] = ['label' => $label, 'icoon' => $icoon, 'url' => $base.$pad, 'sub' => $sub, 'breed' => $breed];
                }
            }
        }

        return view('launcher.index', compact('apps', 'isAdmin', 'admin', 'quickLinks', 'badges', 'dashboardTegels'));
    }

    /** Actuele tellers voor de tegel-bolletjes — gepolld door het dashboard. */
    public function badges(Request $request)
    {
        return AppBadge::where('user_id', $request->user()->id)
            ->pluck('count', 'application_id');
    }

    /**
     * Snelzoeken vanaf het dashboard: klanten, collega's en materieel
     * in één keer. Voor alle ingelogde medewerkers (zelfde data als de
     * read-only API voor child-apps).
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) < 2) {
            return ['customers' => [], 'employees' => [], 'machines' => []];
        }

        $isAdmin = $request->user()->is_super_admin
            || $request->user()->hasRole(['super-admin', 'administrator']);
        $like = '%'.$q.'%';

        $customers = Customer::query()
            ->where(fn ($w) => $w
                ->where('customer_number', 'like', $like)
                ->orWhere('customer_name', 'like', $like)
                ->orWhere('concern_name', 'like', $like))
            ->orderBy('customer_name')->limit(5)
            ->get(['id', 'customer_number', 'customer_name', 'address_city'])
            ->map(fn ($c) => [
                'label' => $c->customer_name,
                'sub'   => trim($c->customer_number.' · '.($c->address_city ?? ''), ' ·'),
                'url'   => $isAdmin ? url('/admin/klanten/'.$c->id) : route('customers.view', $c->id),
            ]);

        $employees = Employee::query()
            ->where('active', true)
            ->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('function', 'like', $like))
            ->orderBy('name')->limit(5)
            ->get(['id', 'name', 'function', 'depot', 'email', 'phone'])
            ->map(fn ($e) => [
                'label' => $e->name,
                'sub'   => trim(($e->function ?? '').' · '.($e->depot ?? ''), ' ·'),
                'email' => $e->email,
                'phone' => $e->phone,
            ]);

        // Materieel: alléén producttypes, en dan wel ALLE treffers — anders
        // zijn types onderaan de lijst nooit te vinden. De resultatenlijst
        // scrollt. Losse artikelnummers zoek je via de Artikelen-pagina.
        $subgroups = \App\Models\MachineSubgroup::query()
            ->where(fn ($w) => $w
                ->where('subgroup_number', 'like', $like)
                ->orWhere('subgroup_name', 'like', $like)
                ->orWhere('merk', 'like', $like)
                ->orWhere('type', 'like', $like))
            ->orderBy('subgroup_name')
            ->get(['id', 'subgroup_number', 'subgroup_name'])
            ->map(fn ($s) => [
                'label' => $s->subgroup_name,
                'sub'   => $s->subgroup_number,
                'url'   => route('articles.subgroup', $s),
            ]);

        return [
            'customers' => $customers,
            'employees' => $employees,
            'subgroups' => $subgroups,
        ];
    }
}
