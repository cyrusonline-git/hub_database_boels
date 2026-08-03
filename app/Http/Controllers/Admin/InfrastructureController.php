<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Employee;
use App\Models\OrgArea;
use App\Models\OrgDepot;
use Illuminate\Http\Request;

class InfrastructureController extends Controller
{
    public function index()
    {
        $units = BusinessUnit::with('areas.depots')->orderBy('sort_order')->orderBy('name')->get();

        // Medewerker-aantallen per area/depot (match op de tekstvelden)
        $employeesPerArea = Employee::whereNull('deleted_at')->whereNotNull('area')
            ->selectRaw('area, count(*) as c')->groupBy('area')->pluck('c', 'area');
        $employeesPerDepot = Employee::whereNull('deleted_at')->whereNotNull('depot')
            ->selectRaw('depot, count(*) as c')->groupBy('depot')->pluck('c', 'depot');

        return view('admin.infrastructure.index', compact('units', 'employeesPerArea', 'employeesPerDepot'));
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:business_units,name']]);
        BusinessUnit::create($data);
        return back()->with('status', 'Business unit toegevoegd.');
    }

    public function storeArea(Request $request)
    {
        $data = $request->validate([
            'business_unit_id' => ['required', 'exists:business_units,id'],
            'name' => ['required', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);
        OrgArea::firstOrCreate(
            ['business_unit_id' => $data['business_unit_id'], 'name' => $data['name']],
            ['country' => $data['country'] ?? null],
        );
        return back()->with('status', 'Area toegevoegd.');
    }

    public function storeDepot(Request $request)
    {
        $data = $request->validate([
            'area_id' => ['required', 'exists:org_areas,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);
        OrgDepot::firstOrCreate(
            ['area_id' => $data['area_id'], 'name' => $data['name']],
            ['email' => $data['email'] ?? null, 'city' => $data['city'] ?? null],
        );
        return back()->with('status', 'Depot toegevoegd.');
    }

    public function updateDepot(Request $request, OrgDepot $depot)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        $oud = $depot->name;
        $depot->update($data);

        // Infrastructuur is leidend: naamswijziging werkt overal door
        if ($oud !== $data['name']) {
            $this->hernoemOveral('depot', $oud, $data['name']);
            return back()->with('status', "Depot hernoemd van \"$oud\" naar \"{$data['name']}\" — automatisch doorgevoerd bij medewerkers, gebruikers en app-restricties.");
        }
        return back()->with('status', 'Depot bijgewerkt.');
    }

    public function updateArea(Request $request, OrgArea $area)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $oud = $area->name;
        $area->update($data);

        if ($oud !== $data['name']) {
            $this->hernoemOveral('area', $oud, $data['name']);
            return back()->with('status', "Area hernoemd van \"$oud\" naar \"{$data['name']}\" — automatisch doorgevoerd bij medewerkers, gebruikers en app-restricties.");
        }
        return back()->with('status', 'Area bijgewerkt.');
    }

    /**
     * Naamswijziging in de Infrastructuur doorvoeren in de hele database:
     * medewerkers, gebruikers-toegangslijsten en app-restricties.
     */
    private function hernoemOveral(string $soort, string $oud, string $nieuw): void
    {
        $kolom = $soort === 'depot' ? 'depot' : 'area';
        $jsonKolom = $soort === 'depot' ? 'allowed_depots' : 'allowed_areas';
        $appKolom = $soort === 'depot' ? 'restricted_to_depots' : 'restricted_to_areas';

        \Illuminate\Support\Facades\DB::table('employees')
            ->where($kolom, $oud)->update([$kolom => $nieuw]);

        foreach (\App\Models\User::whereNotNull($jsonKolom)->get() as $u) {
            $lijst = $u->{$jsonKolom};
            if (is_array($lijst) && in_array($oud, $lijst, true)) {
                $u->forceFill([$jsonKolom => array_values(array_unique(
                    array_map(fn ($v) => $v === $oud ? $nieuw : $v, $lijst)
                ))])->save();
            }
        }

        foreach (\App\Models\Application::whereNotNull($appKolom)->get() as $a) {
            $lijst = $a->{$appKolom};
            if (is_array($lijst) && in_array($oud, $lijst, true)) {
                $a->update([$appKolom => array_values(array_unique(
                    array_map(fn ($v) => $v === $oud ? $nieuw : $v, $lijst)
                ))]);
            }
        }
    }

    public function destroyUnit(BusinessUnit $unit)
    {
        $unit->delete();
        return back()->with('status', 'Business unit verwijderd.');
    }

    public function destroyArea(OrgArea $area)
    {
        $area->delete();
        return back()->with('status', 'Area verwijderd.');
    }

    public function destroyDepot(OrgDepot $depot)
    {
        $depot->delete();
        return back()->with('status', 'Depot verwijderd.');
    }

    /**
     * Leidt de structuur af uit de medewerkerslijst:
     * distinct area (+ meest voorkomend land) en distinct (area, depot).
     * Idempotent — bestaande items blijven staan.
     */
    public function syncFromEmployees()
    {
        $unit = BusinessUnit::withTrashed()->firstOrCreate(['name' => 'Industrial']);
        if ($unit->trashed()) $unit->restore();

        $areasCreated = 0;
        $depotsCreated = 0;

        // Areas met meest voorkomend land
        $areaRows = Employee::whereNull('deleted_at')->whereNotNull('area')
            ->selectRaw('area, country, count(*) as c')
            ->groupBy('area', 'country')->orderByDesc('c')->get()
            ->groupBy('area');

        $areaIds = [];
        foreach ($areaRows as $areaName => $rows) {
            $area = OrgArea::withTrashed()->firstOrCreate(
                ['business_unit_id' => $unit->id, 'name' => trim($areaName)],
                ['country' => $rows->first()->country],
            );
            if ($area->trashed()) $area->restore();
            if ($area->wasRecentlyCreated) $areasCreated++;
            $areaIds[mb_strtolower(trim($areaName))] = $area->id;
        }

        // Depots onder hun area; zonder area → area "Overig"
        $depotRows = Employee::whereNull('deleted_at')->whereNotNull('depot')
            ->selectRaw('depot, area, count(*) as c')
            ->groupBy('depot', 'area')->orderByDesc('c')->get()
            ->groupBy('depot');

        $overigId = null;
        foreach ($depotRows as $depotName => $rows) {
            $areaName = $rows->first()->area;
            $areaId = $areaName ? ($areaIds[mb_strtolower(trim($areaName))] ?? null) : null;

            if (! $areaId) {
                if (! $overigId) {
                    $overig = OrgArea::withTrashed()->firstOrCreate(
                        ['business_unit_id' => $unit->id, 'name' => 'Overig'],
                    );
                    if ($overig->trashed()) $overig->restore();
                    $overigId = $overig->id;
                }
                $areaId = $overigId;
            }

            $depot = OrgDepot::withTrashed()->firstOrCreate(
                ['area_id' => $areaId, 'name' => trim($depotName)],
            );
            if ($depot->trashed()) $depot->restore();
            if ($depot->wasRecentlyCreated) $depotsCreated++;
        }

        return back()->with('status',
            "Structuur bijgewerkt uit medewerkerslijst: $areasCreated nieuwe area(s), $depotsCreated nieuw(e) depot(s). Bestaande items zijn blijven staan.");
    }
}
