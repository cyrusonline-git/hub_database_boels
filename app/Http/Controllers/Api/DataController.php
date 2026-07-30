<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Machine;
use App\Models\MachineSubgroup;
use Illuminate\Http\Request;

/**
 * Read-only data-API voor child-apps.
 * Alles achter auth:sanctum — werkt met de SSO-sessiecookie
 * (cross-subdomein .sorai.nl) of met een Sanctum-token.
 */
class DataController extends Controller
{
    private function perPage(Request $request): int
    {
        return min(200, max(1, (int) $request->input('per_page', 25)));
    }

    // ---- Klanten ----

    public function customers(Request $request)
    {
        return Customer::query()
            ->when($request->filled('q'), fn ($w) => $w->where(fn ($m) => $m
                ->where('customer_number', 'like', '%'.$request->input('q').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('q').'%')
                ->orWhere('concern_name', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('concern'), fn ($w) => $w->where('concern_number', $request->input('concern')))
            ->orderBy('customer_name')
            ->paginate($this->perPage($request));
    }

    public function customer(string $number)
    {
        $customer = Customer::where('customer_number', $number)->first();
        abort_unless($customer, 404, 'Klant niet gevonden.');
        return $customer;
    }

    // ---- Materieel ----

    public function machines(Request $request)
    {
        return Machine::with('subgroup:id,subgroup_number,subgroup_name,group_id')
            ->when($request->filled('q'), fn ($w) => $w->where(fn ($m) => $m
                ->where('machine_number', 'like', '%'.$request->input('q').'%')
                ->orWhere('description', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('subgroup'), fn ($w) => $w->whereHas('subgroup',
                fn ($s) => $s->where('subgroup_number', $request->input('subgroup'))))
            ->orderBy('machine_number')
            ->paginate($this->perPage($request));
    }

    public function machine(string $number)
    {
        $machine = Machine::with('subgroup.group')->where('machine_number', $number)->first();
        abort_unless($machine, 404, 'Machine niet gevonden.');
        return $machine; // subgroup bevat specifications/highlights/etc.
    }

    public function subgroup(string $number)
    {
        $subgroup = MachineSubgroup::with('group')->withCount('machines')
            ->where('subgroup_number', $number)->first();
        abort_unless($subgroup, 404, 'Subgroep niet gevonden.');
        return $subgroup;
    }

    // ---- Personeel ----

    public function employees(Request $request)
    {
        return Employee::query()
            ->select(['id', 'employee_number', 'name', 'email', 'phone', 'function',
                      'department_id', 'depot', 'area', 'country', 'city', 'active'])
            ->when($request->filled('q'), fn ($w) => $w->where(fn ($m) => $m
                ->where('name', 'like', '%'.$request->input('q').'%')
                ->orWhere('email', 'like', '%'.$request->input('q').'%')
                ->orWhere('employee_number', 'like', '%'.$request->input('q').'%')))
            ->when($request->filled('depot'), fn ($w) => $w->where('depot', $request->input('depot')))
            ->when($request->boolean('active', true), fn ($w) => $w->where('active', true))
            ->orderBy('name')
            ->paginate($this->perPage($request));
    }
}
