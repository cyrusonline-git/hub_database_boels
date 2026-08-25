<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineSubgroup;
use Illuminate\Http\Request;

/**
 * Artikelen zoeken en bekijken — voor ALLE ingelogde medewerkers
 * (het Materieel-beheer onder /admin blijft alleen voor admins).
 * Zoek op naam of artikelnummer; specificaties komen uit de
 * geüploade subgroeplijst (Excel).
 */
class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $subgroups = collect();
        $machines = null;

        if ($q !== '') {
            $like = '%'.$q.'%';

            $subgroups = MachineSubgroup::with('group')->withCount('machines')
                ->where(fn ($w) => $w
                    ->where('subgroup_number', 'like', $like)
                    ->orWhere('subgroup_name', 'like', $like)
                    ->orWhere('merk', 'like', $like)
                    ->orWhere('type', 'like', $like))
                ->orderBy('subgroup_name')->get();

            $machines = Machine::with('subgroup')
                ->where(fn ($w) => $w
                    ->where('machine_number', 'like', $like)
                    ->orWhere('description', 'like', $like))
                ->orderBy('machine_number')
                ->paginate(25)->withQueryString();
        }

        return view('articles.index', compact('q', 'subgroups', 'machines'));
    }

    /** Eén uniek materieelnummer — specs via zijn subgroep */
    public function show(Machine $machine)
    {
        $machine->load('subgroup.group');

        return view('articles.show', [
            'machine' => $machine,
            'subgroup' => $machine->subgroup,
        ]);
    }

    /** Artikel op subgroep-niveau: specificaties + bijbehorende nummers */
    public function subgroup(Request $request, MachineSubgroup $subgroup)
    {
        $subgroup->load('group');

        $machines = $subgroup->machines()
            ->orderBy('machine_number')
            ->paginate(50)->withQueryString();

        return view('articles.subgroup', compact('subgroup', 'machines'));
    }
}
