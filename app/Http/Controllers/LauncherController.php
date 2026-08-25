<?php

namespace App\Http\Controllers;

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

        // Handige links (rekentools, documenten) — voor iedereen, per categorie
        $quickLinks = QuickLink::where('active', true)
            ->orderBy('sort_order')->orderBy('title')->get()
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

        return view('launcher.index', compact('apps', 'isAdmin', 'admin', 'quickLinks'));
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
                'url'   => $isAdmin ? url('/admin/klanten/'.$c->id) : null,
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
