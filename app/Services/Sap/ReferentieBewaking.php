<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\InvoiceRunLine;
use App\Models\PlanningOrder;
use App\Models\ReferenceNote;
use App\Models\ReferenceRequest;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PO-bewaking (Wim 13-11-2026): een uitvoerder zegt bij het bestellen vaak "de PO volgt", en daarna raakt
 * het contract uit beeld. Zonder klantreferentie/PO-nummer wordt de factuur niet betaald. Daarom volgen we
 * elk contract zonder referentie over langere tijd:
 *   - in de planning (8.7, kolom REF leeg of 'volgt') zolang de order nog geleverd moet worden;
 *   - daarna in huur (WOW, kolom "Your Reference / Order Number" leeg of 'volgt') tot de referentie in
 *     Smartview staat — dan verdwijnt het contract vanzelf uit de lijst (verdwenen_op) en blijft het in de historie.
 * Handmatig per contract: status open | opgevraagd | afgehandeld + opmerking (reference_notes), overleeft uploads.
 * Logboek (reference_requests): elke keer dat iemand bij de klant om een PO is gevraagd — aan wie, wanneer, door wie —
 * zodat je de klant kunt laten zien dat zijn medewerkers al meerdere keren gevraagd zijn.
 */
class ReferentieBewaking
{
    public function __construct(private PlanningOverzicht $planning) {}

    /** Open bewaking (zonder afgehandeld tenzij gefilterd), met filters employee_id, status, bron, q, alles. */
    public function lijst(array $f = []): Collection
    {
        $rijen = $this->basis();
        if (! empty($f['status'])) {
            $rijen = $rijen->whereIn('status', (array) $f['status']);
        } elseif (empty($f['alles'])) {
            $rijen = $rijen->where('status', '!=', 'afgehandeld');
        }
        if (! empty($f['employee_id'])) {
            $rijen = $rijen->where('employee_id', (int) $f['employee_id']);
        }
        if (! empty($f['bron'])) {
            $rijen = $rijen->where('bron', $f['bron']);
        }
        if (! empty($f['q'])) {
            $s = mb_strtolower($f['q']);
            $rijen = $rijen->filter(fn ($x) => str_contains(mb_strtolower($x->contract_number.' '.$x->customer_name.' '.$x->project), $s));
        }

        return $rijen->sortByDesc('dagen')->values();
    }

    /** Onlangs opgelost: referentie staat er inmiddels (of contract is weg), laatste 30 dagen. */
    public function historie(int $dagen = 30): Collection
    {
        return ReferenceNote::whereNotNull('verdwenen_op')->where('verdwenen_op', '>=', now()->subDays($dagen)->toDateString())
            ->orderByDesc('verdwenen_op')->get()
            ->map(fn ($n) => ['contract_number' => $n->contract_number, 'customer_name' => $n->customer_name, 'eerst_gezien' => $n->eerst_gezien?->toDateString(), 'verdwenen_op' => $n->verdwenen_op?->toDateString(), 'status' => $n->status, 'opmerking' => $n->opmerking])->values();
    }

    /** Basislijst (2 min cache per upload-stand), inclusief synchronisatie van reference_notes. */
    private function basis(): Collection
    {
        return Cache::remember('refbewaking:'.SapUpload::stand(), 120, function () {
            $vandaag = now()->toDateString();
            $rijen = [];

            // 1) In huur (WOW): contract zonder referentie
            if ($wow = SapUpload::laatste('wow')) {
                $c = HireLine::where('upload_id', $wow->id)
                    ->selectRaw('contract_number, MAX(customer_number) AS customer_number, MAX(customer_name) AS customer_name, MAX(project_description) AS project, MAX(your_reference) AS reference, MIN(hire_date) AS sinds, COUNT(*) AS regels, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur, SUM(CASE WHEN status LIKE \'%To Collect%\' THEN 1 ELSE 0 END) AS to_collect')
                    ->groupBy('contract_number')->get();
                foreach ($c as $x) {
                    if (! PlanningOverzicht::referentieOntbreekt($x->reference)) {
                        continue;
                    }
                    $rijen[$x->contract_number] = (object) [
                        'contract_number' => (string) $x->contract_number, 'bron' => 'in_huur',
                        'customer_number' => $x->customer_number, 'customer_name' => $x->customer_name, 'project' => $x->project,
                        'reference' => $x->reference, 'sinds' => $x->sinds ? substr((string) $x->sinds, 0, 10) : null, 'delivery_date' => null,
                        'regels' => (int) $x->regels, 'weekhuur' => (float) $x->weekhuur, 'to_collect' => (int) $x->to_collect,
                        'employee_id' => null, 'employee_name' => null, 'created_by_code' => null,
                    ];
                }
            }
            // 2) Nog te leveren (8.7): order zonder referentie die nog niet (volledig) in huur staat
            foreach ($this->planning->rijen(['referentie_ontbreekt' => 1]) as $o) {
                if (isset($rijen[$o->order_number])) {
                    continue;
                }
                $rijen[$o->order_number] = (object) [
                    'contract_number' => (string) $o->order_number, 'bron' => 'planning',
                    'customer_number' => $o->customer_number, 'customer_name' => $o->customer_name, 'project' => $o->delivery_name,
                    'reference' => $o->reference, 'sinds' => null, 'delivery_date' => $o->delivery_date,
                    'regels' => $o->regels, 'weekhuur' => 0.0, 'to_collect' => 0,
                    'employee_id' => $o->employee_id, 'employee_name' => $o->employee_name, 'created_by_code' => $o->created_by_code,
                ];
            }
            $nrs = array_keys($rijen);

            // Verkoper/aanmaker: laatste 8.7-regel (alle snapshots), anders aanmakerscode uit factuurrun of 3.4
            if ($nrs) {
                $codes = SapUserCode::whereNotNull('employee_id')->pluck('employee_id', 'code');
                $po = PlanningOrder::whereIn('order_number', $nrs)->whereNotNull('employee_id')->orderBy('id')->get(['order_number', 'employee_id'])->keyBy('order_number');
                $fr = InvoiceRunLine::whereIn('contract_number', $nrs)->whereNotNull('created_by_code')->orderBy('id')->get(['contract_number', 'created_by_code'])->keyBy('contract_number');
                $rs = ReservationLine::whereIn('contract_number', $nrs)->whereNotNull('created_by_code')->orderBy('id')->get(['contract_number', 'created_by_code'])->keyBy('contract_number');
                foreach ($rijen as $nr => $r) {
                    if (! $r->employee_id) {
                        $code = $fr[$nr]->created_by_code ?? $rs[$nr]->created_by_code ?? null;
                        $r->employee_id = $po[$nr]->employee_id ?? ($code ? ($codes[$code] ?? null) : null);
                        $r->created_by_code = $r->created_by_code ?: $code;
                    }
                }
                $namen = DB::table('employees')->whereIn('id', collect($rijen)->pluck('employee_id')->filter()->unique())->pluck('name', 'id');
                foreach ($rijen as $r) {
                    $r->employee_name = $r->employee_id ? ($namen[$r->employee_id] ?? null) : null;
                }
            }

            $verzoeken = $this->verzoeken($nrs);
            // 3) Notities synchroniseren: eerst/laatst gezien, verdwenen (= referentie ingevuld of contract weg)
            $notes = ReferenceNote::whereIn('contract_number', $nrs ?: ['-'])->get()->keyBy('contract_number');
            foreach ($rijen as $nr => $r) {
                $n = $notes->get($nr) ?? new ReferenceNote(['contract_number' => $nr, 'status' => 'open', 'eerst_gezien' => $r->sinds && $r->sinds < $vandaag ? $r->sinds : $vandaag]);
                $n->customer_name = $r->customer_name;
                $n->verdwenen_op = null;
                $n->laatst_gezien = $vandaag;
                $n->save();
                $eerst = $n->eerst_gezien?->toDateString() ?? $vandaag;
                if ($r->sinds && $r->sinds < $eerst) {
                    $eerst = $r->sinds;
                }
                $r->eerst_gezien = $eerst;
                $r->dagen = max(0, (int) \Carbon\Carbon::parse($eerst)->startOfDay()->diffInDays(now()->startOfDay()));
                $r->status = $n->status;
                $r->opmerking = $n->opmerking;
                $r->opgevraagd_op = $n->opgevraagd_op?->toDateString();
                $r->note_user_id = $n->user_id;
                $r->note_updated_at = $n->updated_at?->toDateTimeString();
                $v = $verzoeken[$nr] ?? [];
                $r->verzoeken = $v;
                $r->keer_gevraagd = count($v);
                $r->laatst_gevraagd_op = $v[0]['op'] ?? null;
                $r->laatst_gevraagd_aan = $v[0]['aan'] ?? null;
            }
            ReferenceNote::whereNull('verdwenen_op')->whereNotIn('contract_number', $nrs ?: ['-'])->update(['verdwenen_op' => $vandaag]);

            return collect(array_values($rijen));
        });
    }

    /** Logboek per contract, nieuwste eerst: [['op','aan','door','opmerking'], …]. */
    public function verzoeken(array $nrs): array
    {
        if (! $nrs) {
            return [];
        }
        $uit = [];
        foreach (ReferenceRequest::with('user:id,name')->whereIn('contract_number', $nrs)->orderByDesc('gevraagd_op')->orderByDesc('id')->get() as $v) {
            $uit[$v->contract_number][] = ['id' => $v->id, 'op' => $v->gevraagd_op?->toDateString(), 'aan' => $v->gevraagd_aan, 'door' => $v->user?->name, 'opmerking' => $v->opmerking];
        }

        return $uit;
    }

    /**
     * Handmatige status/opmerking. Met 'gevraagd_aan' (of 'log' = 1) komt er een regel in het logboek bij en gaat de
     * status naar 'opgevraagd' (tenzij expliciet 'afgehandeld'); het contract blijft open tot de referentie in Smartview staat.
     */
    public function notitie(string $nr, array $data, ?int $userId): ReferenceNote
    {
        $n = ReferenceNote::firstOrNew(['contract_number' => $nr]);
        $aan = trim((string) ($data['gevraagd_aan'] ?? ''));
        if ($aan !== '' || ! empty($data['log'])) {
            ReferenceRequest::create(['contract_number' => $nr, 'gevraagd_aan' => $aan ?: null, 'opmerking' => $data['opmerking'] ?? null, 'user_id' => $userId, 'gevraagd_op' => $data['gevraagd_op'] ?? now()->toDateString()]);
            if (($data['status'] ?? null) !== 'afgehandeld') {
                $data['status'] = 'opgevraagd';
            }
        }
        if (array_key_exists('opmerking', $data)) {
            $n->opmerking = $data['opmerking'];
        }
        if (! empty($data['status'])) {
            $n->status = $data['status'];
            if ($data['status'] === 'opgevraagd' && ! $n->opgevraagd_op) {
                $n->opgevraagd_op = now()->toDateString();
            }
        }
        $n->user_id = $userId;
        $n->eerst_gezien ??= now()->toDateString();
        $n->save();
        Cache::forget('refbewaking:'.SapUpload::stand());

        return $n;
    }
}
