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
