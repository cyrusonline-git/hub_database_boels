<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineGroup;
use App\Models\MachineSubgroup;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /** Overzicht van subgroepen, filterbaar op analysegroep / productgroep / zoekterm */
    public function index(Request $request)
    {
        $query = MachineSubgroup::query()
            ->with('group')
            ->withCount('machines');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(fn ($w) => $w
                ->where('subgroup_number', 'like', "%$q%")
                ->orWhere('subgroup_name', 'like', "%$q%")
                ->orWhere('merk', 'like', "%$q%")
                ->orWhere('type', 'like', "%$q%"));
        }
        if ($request->filled('group')) {
            $query->where('group_id', $request->input('group'));
        }
        if ($request->filled('analysis')) {
            $query->whereHas('group', fn ($w) => $w->where('analysis_group', $request->input('analysis')));
        }
        if ($request->input('specs') === 'met') {
            $query->whereNotNull('specifications');
        } elseif ($request->input('specs') === 'zonder') {
            $query->whereNull('specifications');
        }

        $subgroups = $query->orderBy('subgroup_number')->paginate(50)->withQueryString();

        return view('admin.material.index', [
            'subgroups' => $subgroups,
            'groups' => MachineGroup::orderBy('group_name')->get(['id', 'group_name', 'analysis_group']),
            'analysisGroups' => MachineGroup::whereNotNull('analysis_group')
                ->distinct()->orderBy('analysis_group')->pluck('analysis_group'),
            'stats' => [
                'machines' => Machine::count(),
                'subgroups' => MachineSubgroup::count(),
                'withSpecs' => MachineSubgroup::whereNotNull('specifications')->count(),
                'groups' => MachineGroup::count(),
            ],
        ]);
    }

    /** Detail van één subgroep: alle specs + de unieke machines eronder */
    public function show(Request $request, MachineSubgroup $subgroup)
    {
        $subgroup->load('group');

        $machines = $subgroup->machines()
            ->when($request->filled('q'), fn ($w) => $w->where(fn ($m) => $m
                ->where('machine_number', 'like', '%'.$request->input('q').'%')
                ->orWhere('description', 'like', '%'.$request->input('q').'%')))
            ->orderBy('machine_number')
            ->paginate(50)->withQueryString();

        return view('admin.material.show', compact('subgroup', 'machines'));
    }

    /** Zoeken over alle unieke materieelnummers heen */
    public function machines(Request $request)
    {
        $machines = Machine::with('subgroup.group')
            ->when($request->filled('q'), fn ($w) => $w->where(fn ($m) => $m
                ->where('machine_number', 'like', '%'.$request->input('q').'%')
                ->orWhere('description', 'like', '%'.$request->input('q').'%')))
            ->orderBy('machine_number')
            ->paginate(50)->withQueryString();

        return view('admin.material.machines', compact('machines'));
    }
}
