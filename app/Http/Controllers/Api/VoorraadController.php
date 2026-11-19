<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sap\VlootData;
use App\Services\Sap\VoorraadCheck;
use Illuminate\Http\Request;

/** Voorraadcheck per contract + vlootvoorraad (meters, werkplaats) voor de Dashboard-app. */
class VoorraadController extends Controller
{
    public function __construct(private VoorraadCheck $check, private VlootData $vloot) {}

    public function contracten(Request $r)
    {
        return ['data' => $this->check->contracten($r->input('depot') ?: null, $r->filled('employee_id') ? (int) $r->input('employee_id') : null)];
    }

    public function contract(Request $r, string $nr)
    {
        abort_unless(preg_match('/^\d{6,12}$/', $nr), 422, 'Ongeldig contractnummer.');

        return $this->check->contract($nr, [
            'depot' => $r->input('depot') ?: null,
            'horizon' => $r->input('horizon') ? (int) $r->input('horizon') : 21,
            'offertes' => $r->input('offertes', '1') !== '0',
            'onderhoud' => $r->input('onderhoud') === '1',
        ]);
    }

    public function vraag(Request $r)
    {
        return ['data' => $this->check->vraag((string) $r->input('depot', '759'), (int) ($r->input('horizon') ?: 14), $r->input('offertes', '1') !== '0')];
    }

    /** Stockplanner: matrix per depot (rijen subgroepen, kolommen dagen/weken). */
    public function planner(Request $r, \App\Services\Sap\StockPlanner $p)
    {
        return $p->matrix((string) ($r->input('depot') ?: config('sap.depot') ?: '759'), $this->plannerOpties($r) + [
            'q' => $r->input('q'), 'alleen_tekort' => $r->boolean('alleen_tekort'), 'alleen_mutaties' => $r->input('alleen_mutaties', '1') !== '0',
            'zonder_vloot' => $r->boolean('zonder_vloot'),
            'per' => $r->input('per', 'dag'), 'limiet' => (int) ($r->input('limiet') ?: 400),
        ]);
    }

    /** Stockplanner: één subgroep, tijdlijn per depot + mutaties. */
    public function plannerSubgroep(Request $r, string $groep, \App\Services\Sap\StockPlanner $p)
    {
        return $p->subgroep($groep, $r->input('depot') ?: null, $this->plannerOpties($r));
    }

    private function plannerOpties(Request $r): array
    {
        return [
            'horizon' => (int) ($r->input('horizon') ?: 28),
            'retour_dagen' => (int) $r->input('retour_dagen', 14),
            'offertes' => $r->input('offertes', '1') !== '0',
            'onderhoud' => $r->input('onderhoud') === '1',
            'reparatie' => $r->input('reparatie') === '1',
        ];
    }

    public function depots()
    {
        return $this->check->depots();
    }

    public function stand(Request $r)
    {
        return $this->vloot->stand($r->input('depot') ?: null, (int) ($r->input('dagen') ?: 30));
    }

    public function subgroepen(Request $r)
    {
        $groepen = array_values(array_filter(array_map('trim', explode(',', (string) $r->input('groepen', '')))));

        return ['data' => $this->vloot->subgroepen($r->input('depot') ?: null, $r->input('q') ?: null, $groepen ?: null, (int) ($r->input('limiet') ?: 500))];
    }

    public function subgroep(string $groep)
    {
        return $this->vloot->subgroep($groep);
    }

    public function machines(Request $r)
    {
        $groepen = array_values(array_filter(array_map('trim', explode(',', (string) $r->input('groepen', '')))));
        $statussen = array_values(array_filter(explode(',', (string) $r->input('statussen', 'onderhoud,reparatie'))));

        return ['data' => $this->vloot->machines((string) $r->input('depot'), $groepen, $statussen)];
    }
}
