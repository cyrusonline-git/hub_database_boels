<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineGroup;
use App\Models\MachineSubgroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    /** Niveau 1: analysegroepen (hoogste niveau), doorklikbaar */
    public function index()
    {
        $analysisGroups = DB::table('machine_groups as g')
            ->leftJoin('machine_subgroups as sg', fn ($j) => $j->on('sg.group_id', '=', 'g.id')->whereNull('sg.deleted_at'))
            ->leftJoin('machines as m', fn ($j) => $j->on('m.subgroup_id', '=', 'sg.id')->whereNull('m.deleted_at'))
            ->whereNull('g.deleted_at')
            ->selectRaw("coalesce(g.analysis_group, 'Overig / onbekend') as analysis_group,
                         count(distinct case when sg.id is not null then g.id end) as groups_count,
                         count(distinct sg.id) as subgroups_count,
                         count(distinct m.id) as machines_count")
            ->groupBy('analysis_group')
            ->havingRaw('count(distinct sg.id) > 0')
            ->orderBy('analysis_group')
            ->get();

        return view('admin.material.index', [
            'analysisGroups' => $analysisGroups,
            'stats' => [
                'machines' => Machine::count(),
                'subgroups' => MachineSubgroup::count(),
                'withSpecs' => MachineSubgroup::whereNotNull('specifications')->count(),
                'groups' => MachineGroup::count(),
            ],
        ]);
    }

    /** Niveau 2: productgroepen binnen een analysegroep */
    public function groups(Request $request)
    {
        $analysis = $request->input('analysis', '');

        $groups = MachineGroup::query()
            ->when($analysis === 'Overig / onbekend',
                fn ($q) => $q->whereNull('analysis_group'),
                fn ($q) => $q->where('analysis_group', $analysis))
            ->has('subgroups')
            ->withCount('subgroups')
            ->addSelect(['machines_count' => Machine::selectRaw('count(*)')
                ->join('machine_subgroups as sg2', 'sg2.id', '=', 'machines.subgroup_id')
                ->whereColumn('sg2.group_id', 'machine_groups.id')
                ->whereNull('sg2.deleted_at')])
            ->orderBy('group_name')
            ->get();

        return view('admin.material.groups', compact('groups', 'analysis'));
    }

    /** Niveau 3: subgroepen — vanuit een productgroep of als doorzoekbare lijst */
    public function subgroups(Request $request)
    {
        $query = MachineSubgroup::query()
            ->with('group')
            ->withCount('machines');

        $group = null;
        if ($request->filled('group')) {
            $group = MachineGroup::find($request->input('group'));
            $query->where('group_id', $request->input('group'));
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(fn ($w) => $w
                ->where('subgroup_number', 'like', "%$q%")
                ->orWhere('subgroup_name', 'like', "%$q%")
                ->orWhere('merk', 'like', "%$q%")
                ->orWhere('type', 'like', "%$q%"));
        }
        if ($request->input('specs') === 'met') {
            $query->whereNotNull('specifications');
        } elseif ($request->input('specs') === 'zonder') {
            $query->whereNull('specifications');
        }

        $subgroups = $query->orderBy('subgroup_number')->paginate(50)->withQueryString();

        return view('admin.material.subgroups', compact('subgroups', 'group'));
    }

    /** Niveau 4: één subgroep met specs + de unieke machines eronder */
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

    /** Niveau 5: één unieke machine — specs komen via zijn subgroep */
    public function machine(Machine $machine)
    {
        $machine->load('subgroup.group');

        return view('admin.material.machine', [
            'machine' => $machine,
            'subgroup' => $machine->subgroup,
        ]);
    }

    /** Vrij zoeken over alle unieke materieelnummers heen */
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
