<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceRunNote;
use App\Models\PlanningNote;
use App\Models\SapUpload;
use App\Services\Sap\HireData;
use App\Services\Sap\PlanningOverzicht;
use Illuminate\Http\Request;

/**
 * SAP-data voor child-apps (Dashboard, Shell/BP/Damen/RMW, Projectschade, Tankapp).
 * Alles leest de LAATSTE snapshot per bestandssoort; schrijven kan alleen op de
 * handmatige velden (notities) en wordt op naam van de ingelogde CORE-gebruiker gelogd.
 */
class SapController extends Controller
{
    public function __construct(private HireData $hire, private PlanningOverzicht $planning) {}

    /** Laatste stand per bestandssoort (voor de "stand van …"-balk). */
    public function uploads()
    {
        $uit = [];
        foreach ((array) config('sap.soorten') as $soort => $cfg) {
            if (! empty($cfg['verborgen'])) {
                continue;
            }
            $u = SapUpload::laatste($soort);
            $uit[$soort] = ($this->hire->uploadInfo($u) ?? ['id' => null]) + ['label' => $cfg['label'], 'bestand' => $cfg['bestand'], 'frequentie_dagen' => $cfg['frequentie_dagen'], 'verouderd' => $u ? $u->created_at->diffInDays(now()) > $cfg['frequentie_dagen'] + 1 : true];
        }

        return $uit;
    }

    public function summary()
    {
        return $this->hire->samenvatting();
    }

    public function contracts(Request $r)
    {
        $rijen = $this->hire->contracten($r->only(['customer', 'project', 'status', 'q', 'sort']));
        $per = min(500, max(10, (int) $r->input('per_page', 100)));
        $pagina = max(1, (int) $r->input('page', 1));

        return ['total' => $rijen->count(), 'page' => $pagina, 'per_page' => $per, 'upload' => $this->hire->uploadInfo($this->hire->laatsteUpload('wow')), 'data' => $rijen->forPage($pagina, $per)->values()];
    }

    public function contract(string $nr)
    {
        return $this->hire->contract($nr) ?? abort(404, 'Contract niet in de laatste WOW-export.');
    }

    public function toCollect(Request $r)
    {
        return ['upload' => $this->hire->uploadInfo($this->hire->laatsteUpload('wow')), 'data' => $this->hire->opTeHalen($r->input('van'), $r->input('tm'))];
    }

    public function machine(string $nummer)
    {
        $regels = $this->hire->machine($nummer);

        return ['machine_number' => $nummer, 'in_huur' => $regels->isNotEmpty(), 'data' => $regels];
    }

    public function invoiceRun(Request $r)
    {
        $rijen = $this->hire->factuurrun($r->only(['employee_id', 'customer', 'status', 'zonder_reden', 'nooit']));

        return [
            'upload' => $this->hire->uploadInfo($this->hire->laatsteUpload('factuurrun')),
            'totaal' => ['contracten' => $rijen->count(), 'zonder_reden' => $rijen->filter(fn ($x) => ! $x->reden)->count(), 'nooit_gefactureerd' => $rijen->where('nooit_gefactureerd', true)->count(), 'weekhuur' => round($rijen->sum('weekhuur'), 2)],
            'data' => $rijen,
        ];
    }

    public function invoiceRunContract(string $nr)
    {
        return $this->hire->factuurrunContract($nr) ?? abort(404, 'Contract niet in de laatste factuurrun-export.');
    }

    public function invoiceRunNote(Request $r, string $nr)
    {
        $data = $r->validate(['reden' => ['nullable', 'string', 'max:2000'], 'status' => ['nullable', 'in:te_controleren,verklaard,opgelost']]);
        $n = InvoiceRunNote::firstOrNew(['contract_number' => $nr]);
        if (array_key_exists('reden', $data)) {
            $n->reden = $data['reden'];
        }
        if (! empty($data['status'])) {
            $n->status = $data['status'];
        } elseif (! empty($data['reden']) && $n->status === 'te_controleren') {
            $n->status = 'verklaard';
        }
        $n->user_id = $r->user()?->id;
        $n->eerst_gezien ??= now()->toDateString();
        $n->save();

        return ['ok' => true, 'note' => $n];
    }

    public function planning(Request $r)
    {
        $rijen = $this->planning->rijen($r->only(['van', 'tm', 'soort', 'employee_id', 'q', 'verleden', 'alles', 'bijhuur']));

        return [
            'uploads' => ['planning' => $this->hire->uploadInfo(SapUpload::laatste('planning')), 'reserveringen' => $this->hire->uploadInfo(SapUpload::laatste('reserveringen'))],
            'totaal' => ['orders' => $rijen->count(), 'per_soort' => $rijen->countBy('soort'), 'per_pick_status' => $rijen->countBy('pick_status'), 'te_laat' => $rijen->where('te_laat', true)->count()],
            'data' => $rijen,
        ];
    }

    public function planningOrder(string $nr)
    {
        return $this->planning->order($nr) ?? abort(404, 'Order niet in de laatste planning-exports.');
    }

    public function planningNote(Request $r, string $nr)
    {
        $data = $r->validate(['expeditie_employee_id' => ['nullable', 'integer', 'exists:employees,id'], 'opmerking' => ['nullable', 'string', 'max:2000'], 'vlag' => ['nullable', 'boolean']]);
        $n = PlanningNote::firstOrNew(['order_number' => $nr]);
        $n->fill($data);
        $n->user_id = $r->user()?->id;
        $n->save();

        return ['ok' => true, 'note' => $n];
    }
}
