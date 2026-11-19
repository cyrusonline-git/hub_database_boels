<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\InvoiceRunLine;
use App\Models\InvoiceRunNote;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Leesvragen op de laatste WOW- en factuurrun-snapshot (voor de API en het dashboard). */
class HireData
{
    public function laatsteUpload(string $soort): ?SapUpload
    {
        return SapUpload::laatste($soort);
    }

    /** Totalen uit de laatste WOW: weekhuur, per klant, per project, per status, ophaallijst-teller. */
    public function samenvatting(): array
    {
        $u = $this->laatsteUpload('wow');
        if (! $u) {
            return ['beschikbaar' => false];
        }
        $basis = HireLine::where('upload_id', $u->id);
        $perKlant = (clone $basis)->selectRaw('customer_number, MAX(customer_name) AS customer_name, COUNT(DISTINCT contract_number) AS contracten, COUNT(*) AS regels, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur, SUM(CASE WHEN status LIKE ? THEN 1 ELSE 0 END) AS to_collect', ['%To Collect%'])
            ->groupBy('customer_number')->orderByDesc('weekhuur')->get();
        $perProject = (clone $basis)->whereNotNull('project_number')->selectRaw('project_number, MAX(project_description) AS project_description, MAX(customer_name) AS customer_name, COUNT(DISTINCT contract_number) AS contracten, COUNT(*) AS regels, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur')
            ->groupBy('project_number')->orderByDesc('weekhuur')->get();
        $perStatus = (clone $basis)->selectRaw('status, COUNT(*) AS regels, COUNT(DISTINCT contract_number) AS contracten')->groupBy('status')->get();
        // Trend: één punt per upload-dag (laatste upload van die dag telt), met top-klanten per punt
        $trend = SapUpload::where('soort', 'wow')->orderByDesc('id')->limit(400)->get(['id', 'created_at', 'rapportdatum', 'samenvatting', 'contracten'])
            ->unique(fn ($x) => $x->created_at->toDateString())
            ->map(fn ($x) => ['datum' => $x->created_at->toDateString(), 'moment' => $x->created_at->toDateTimeString(), 'weekhuur' => $x->samenvatting['weekhuur'] ?? null, 'contracten' => $x->contracten, 'klanten' => $x->samenvatting['klanten'] ?? null, 'to_collect' => $x->samenvatting['to_collect_regels'] ?? null, 'top_klanten' => $x->samenvatting['top_klanten'] ?? []])
            ->reverse()->values();
        // Weekhuur naar startmaand van de huur (hoe lang staat het al uit) en per status
        $perMaand = (clone $basis)->whereNotNull('hire_date')->selectRaw('substr(hire_date, 1, 7) AS maand, COUNT(DISTINCT contract_number) AS contracten, COUNT(*) AS regels, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur')
            ->groupBy('maand')->orderBy('maand')->get();
        $perStatusGeld = (clone $basis)->selectRaw('status, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur')->groupBy('status')->get();
        $projectGeld = (float) (clone $basis)->whereNotNull('project_number')->selectRaw('ROUND(SUM(weekly_hire_rate*qty_out),2) AS w')->value('w');

        return [
            'beschikbaar' => true,
            'upload' => $this->uploadInfo($u),
            'weekhuur' => (float) ($u->samenvatting['weekhuur'] ?? 0),
            'contracten' => $u->contracten,
            'regels' => $u->regels,
            'to_collect_regels' => (int) ((clone $basis)->where('status', 'like', '%To Collect%')->count()),
            'to_collect_contracten' => (int) ((clone $basis)->where('status', 'like', '%To Collect%')->distinct()->count('contract_number')),
            'per_klant' => $perKlant,
            'per_project' => $perProject,
            'per_status' => $perStatus,
            'per_status_weekhuur' => $perStatusGeld,
            'per_maand' => $perMaand,
            'weekhuur_projecten' => $projectGeld,
            'trend' => $trend,
            'maand' => $this->maandprognose($u->id),
        ];
    }

    /**
     * Omzetprognose lopende maand (Wim 13-11-2026): als alles wat nu in huur staat tot het einde van de maand blijft
     * staan, wat is dan de huuromzet van deze maand? Per regel: netto weekhuur × aantal × dagen in huur deze maand / 7,
     * gerekend vanaf de huurstart (of de 1e van de maand als de huur eerder begon) t/m de laatste dag van de maand.
     * Daarnaast: het deel t/m vandaag, het deel dat nog komt, en de prognose MET einddatum huur (regels met een bekende
     * afmeld-/ophaaldatum stoppen op die datum) — die laatste staat op het managementoverzicht.
     */
    public function maandprognose(int $uploadId): array
    {
        $start = now()->startOfMonth();
        $eind = now()->endOfMonth();
        $vandaag = now()->startOfDay();
        $dagenMaand = (int) $start->diffInDays($eind) + 1;
        $tot = $totVandaag = $totRealistisch = 0.0;
        $perKlant = [];
        $dag = fn ($x) => $x ? \Carbon\Carbon::parse(substr((string) $x, 0, 10))->startOfDay() : null;
        HireLine::where('upload_id', $uploadId)->select(['customer_number', 'customer_name', 'contract_number', 'hire_date', 'coll_date', 'est_return_date', 'status', 'weekly_hire_rate', 'qty_out'])
            ->orderBy('id')->chunk(2000, function ($regels) use (&$tot, &$totVandaag, &$totRealistisch, &$perKlant, $start, $eind, $vandaag, $dag) {
                foreach ($regels as $l) {
                    $week = (float) $l->weekly_hire_rate * (float) $l->qty_out;
                    if ($week <= 0) {
                        continue;
                    }
                    $van = $dag($l->hire_date) ?? $start;
                    if ($van->lt($start)) {
                        $van = $start->copy();
                    }
                    if ($van->gt($eind)) {
                        continue;   // huur begint pas na deze maand
                    }
                    $dagen = (int) $van->diffInDays($eind) + 1;
                    $bedrag = $week * $dagen / 7;
                    $tot += $bedrag;
                    $dagenTot = $van->lte($vandaag) ? min($dagen, (int) $van->diffInDays($vandaag) + 1) : 0;
                    $totVandaag += $week * $dagenTot / 7;
                    // Met einddatum huur: regels met een bekende afmeld-/ophaaldatum (WOW Coll. Date / EstRtDate, leeg =
                    // onbekend) stoppen op die datum; ligt die al in het verleden maar staat de regel nog uit, dan t/m vandaag
                    $tot2 = $eind;
                    if (($c = $dag($l->coll_date ?? $l->est_return_date)) && $c->lt($eind)) {
                        $tot2 = $c->lt($vandaag) ? $vandaag : $c;
                    }
                    $dagen2 = $tot2->lt($van) ? 0 : (int) $van->diffInDays($tot2) + 1;
                    $totRealistisch += $week * $dagen2 / 7;
                    $k = $l->customer_number ?: $l->customer_name;
                    $perKlant[$k] ??= ['customer_number' => $l->customer_number, 'customer_name' => $l->customer_name, 'omzet' => 0.0, 'contracten' => []];
                    $perKlant[$k]['omzet'] += $bedrag;
                    $perKlant[$k]['contracten'][$l->contract_number] = true;
                }
            });
        $klanten = collect($perKlant)->map(fn ($k) => ['customer_number' => $k['customer_number'], 'customer_name' => $k['customer_name'], 'omzet' => round($k['omzet'], 2), 'contracten' => count($k['contracten'])])
            ->sortByDesc('omzet')->values();

        return [
            'maand' => $start->format('Y-m'),
            'maand_label' => \Illuminate\Support\Str::ucfirst($start->locale('nl')->translatedFormat('F Y')),
            'dagen_maand' => $dagenMaand,
            'dagen_voorbij' => (int) $start->diffInDays($vandaag) + 1,
            'dagen_rest' => max(0, (int) $vandaag->diffInDays($eind)),
            'prognose' => round($tot, 2),
            'tot_vandaag' => round($totVandaag, 2),
            'nog_te_komen' => round($tot - $totVandaag, 2),
            'prognose_met_einddatum' => round($totRealistisch, 2),
            'prognose_realistisch' => round($totRealistisch, 2),
            'tot_vandaag_pct' => $dagenMaand > 0 ? round(((int) $start->diffInDays($vandaag) + 1) / $dagenMaand * 100) : 0,
            'per_klant' => $klanten->take(15)->values(),
            'klanten' => $klanten->count(),
        ];
    }

    /** Contracten in huur (laatste WOW), gegroepeerd per contract, met filters. */
    public function contracten(array $f = []): Collection
    {
        $u = $this->laatsteUpload('wow');
        if (! $u) {
            return collect();
        }
        $q = HireLine::where('upload_id', $u->id)
            ->selectRaw('contract_number, MAX(customer_number) AS customer_number, MAX(customer_name) AS customer_name, MAX(customer_id) AS customer_id, MAX(project_number) AS project_number, MAX(project_description) AS project_description,
                MAX(delivery_name) AS delivery_name, MAX(delivery_address) AS delivery_address, MAX(delivery_postal_code) AS delivery_postal_code, MAX(your_reference) AS your_reference,
                COUNT(*) AS regels, SUM(qty_out) AS aantal, MIN(hire_date) AS hire_date, MIN(coll_date) AS coll_date, MAX(est_return_date) AS est_return_date,
                ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur, SUM(CASE WHEN status LIKE \'%To Collect%\' THEN 1 ELSE 0 END) AS to_collect_regels, MAX(status) AS status')
            ->groupBy('contract_number');
        if (! empty($f['customer'])) {
            $q->where(fn ($w) => $w->where('customer_number', $f['customer'])->orWhere('customer_name', 'like', '%'.$f['customer'].'%'));
        }
        if (! empty($f['project'])) {
            $q->where('project_number', $f['project']);
        }
        if (! empty($f['status'])) {
            $q->where('status', 'like', '%'.$f['status'].'%');
        }
        if (! empty($f['q'])) {
            $s = '%'.$f['q'].'%';
            $q->where(fn ($w) => $w->where('contract_number', 'like', $s)->orWhere('customer_name', 'like', $s)->orWhere('project_description', 'like', $s)->orWhere('your_reference', 'like', $s)->orWhere('item_number', 'like', $s)->orWhere('item_description', 'like', $s));
        }
        $sort = in_array($f['sort'] ?? '', ['weekhuur', 'hire_date', 'customer_name', 'coll_date'], true) ? $f['sort'] : 'weekhuur';

        return $q->orderByDesc($sort)->get();
    }

    public function contract(string $nr): ?array
    {
        $u = $this->laatsteUpload('wow');
        if (! $u) {
            return null;
        }
        $regels = HireLine::where('upload_id', $u->id)->where('contract_number', $nr)->orderBy('item_description')->get();
        if ($regels->isEmpty()) {
            return null;
        }
        $kop = $regels->first();

        return [
            'contract_number' => $nr,
            'customer_number' => $kop->customer_number, 'customer_name' => $kop->customer_name, 'customer_id' => $kop->customer_id,
            'project_number' => $kop->project_number, 'project_description' => $kop->project_description,
            'delivery_name' => $kop->delivery_name, 'delivery_address' => $kop->delivery_address, 'delivery_postal_code' => $kop->delivery_postal_code,
            'your_reference' => $kop->your_reference, 'hire_date' => $regels->min('hire_date')?->toDateString(),
            'weekhuur' => round($regels->sum(fn ($r) => $r->weekly_hire_rate * $r->qty_out), 2),
            'regels' => $regels,
            'upload' => $this->uploadInfo($u),
            'open_order' => $this->openOrder($nr),
        ];
    }

    /** Nog te leveren op dit contract volgens 8.7 (bijhuur op een lopend contract) + eventuele 2.6-regels. */
    public function openOrder(string $nr): ?array
    {
        $pl = SapUpload::laatste('planning');
        $o = $pl ? PlanningOrder::where('upload_id', $pl->id)->where('order_number', $nr)->first() : null;
        if (! $o) {
            return null;
        }
        $rs = SapUpload::laatste('reserveringen');
        $regels = $rs ? ReservationLine::where('upload_id', $rs->id)->where('contract_number', $nr)->orderBy('delivery_date')->get(['item_number', 'item_description', 'qty', 'delivery_date']) : collect();

        return ['order_number' => $o->order_number, 'soort' => $o->soort, 'delivery_date' => $o->delivery_date?->toDateString(), 'contract_status' => $o->contract_status, 'picked' => (float) $o->picked, 'total' => (float) $o->total, 'nog_te_picken' => max(0, (float) $o->total - (float) $o->picked), 'sales_person' => $o->sales_person, 'reference' => $o->reference, 'regels_26' => $regels];
    }

    /** Ophaallijst: regels met status To Collect, per contract, gesorteerd op ophaaldatum. */
    public function opTeHalen(?string $van = null, ?string $tm = null): Collection
    {
        $u = $this->laatsteUpload('wow');
        if (! $u) {
            return collect();
        }
        $q = HireLine::where('upload_id', $u->id)->where('status', 'like', '%To Collect%')
            ->selectRaw('contract_number, MAX(customer_name) AS customer_name, MAX(customer_number) AS customer_number, MAX(delivery_name) AS delivery_name, MAX(delivery_address) AS delivery_address, MAX(delivery_postal_code) AS delivery_postal_code,
                MIN(coll_date) AS coll_date, MAX(coll_time) AS coll_time, MAX(coll_doc_printed) AS coll_doc_printed, COUNT(*) AS regels, SUM(qty_out) AS aantal, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur, MAX(project_description) AS project_description')
            ->groupBy('contract_number');
        if ($van) {
            $q->havingRaw('MIN(coll_date) >= ?', [$van]);
        }
        if ($tm) {
            $q->havingRaw('MIN(coll_date) <= ?', [$tm]);
        }
        $vandaag = now()->toDateString();

        return $q->orderByRaw('CASE WHEN MIN(coll_date) IS NULL THEN 1 ELSE 0 END')->orderBy('coll_date')->get()
            ->map(function ($r) use ($vandaag) {
                $r->te_laat = $r->coll_date && $r->coll_date < $vandaag;
                $r->klant_haalt_af = mb_strtolower((string) $r->delivery_name) === 'afhalen';

                return $r;
            });
    }

    /** Waar staat een machine (machinenummer uit WOW)? */
    public function machine(string $nummer): Collection
    {
        $u = $this->laatsteUpload('wow');

        return $u ? HireLine::where('upload_id', $u->id)->where('item_number', $nummer)->get() : collect();
    }

    /**
     * Opzoeken voor de schade-app en de tankapp: bij welke klant/contract/project hoort deze machine?
     * Volgorde: 1) goederen-in (0.05: net retour, nog niet afgemeld — daar wordt afgetankt en schade gezien),
     * 2) de laatste WOW (nog in huur), 3) de meest recente bewaarde oudere WOW-upload waarin het nummer voorkomt.
     * Projectnummer/-omschrijving ontbreken in de 0.05 → via het contract uit de WOW-historie of de 8.A.
     * Geeft alleen de velden die de apps nodig hebben (geen tarieven/notities).
     */
    public function machineVoorSchade(string $nummer): array
    {
        $nr = strtoupper(preg_replace('/[\s\-]+/', '', trim($nummer)));
        $uit = ['machine_number' => $nr, 'gevonden' => false, 'historisch' => false, 'bron' => null, 'aantal_treffers' => 0, 'data' => null, 'treffers' => []];
        if ($nr === '') {
            return $uit;
        }
        $antwoord = function (Collection $regels, string $bron, bool $historisch, ?string $moment) use ($nr) {
            $contracten = $regels->pluck('contract_number')->unique()->values();
            $map = fn (array $r) => array_merge($r, $this->projectVoorContract((string) $r['contract_number'], $r['project_number'] ?? null, $r['project_description'] ?? null));

            return [
                'machine_number' => $nr, 'gevonden' => true, 'historisch' => $historisch, 'bron' => $bron, 'upload_moment' => $moment,
                'aantal_treffers' => $contracten->count(),
                // eenduidig = één contract; bij bulknummers op meerdere contracten niet automatisch invullen
                'data' => $contracten->count() === 1 ? $map($regels->first()) : null,
                'treffers' => $regels->take(10)->map($map)->values()->all(),
            ];
        };
        $d = fn ($v) => $v ? (is_string($v) ? substr($v, 0, 10) : $v->format('Y-m-d')) : null;

        // 1) goederen-in (0.05)
        $gi = \App\Models\SapUpload::laatste('goederen_in');
        if ($gi) {
            $regels = \App\Models\GoodsInLine::where('upload_id', $gi->id)->where('item_number', $nr)->orderByDesc('goods_in_date')->get()
                ->map(fn ($r) => [
                    'contract_number' => $r->contract_number, 'customer_name' => $r->customer_name, 'customer_number' => $r->customer_number,
                    'project_number' => null, 'project_description' => null, 'delivery_name' => $r->delivery_name, 'city' => $r->city,
                    'status' => 'Goederen in', 'depot' => substr((string) $r->contract_number, 0, 3), 'hire_date' => $d($r->hire_start_date),
                    'coll_date' => $d($r->collection_date), 'goods_in_date' => $d($r->goods_in_date), 'item_description' => $r->item_description, 'qty_out' => (float) $r->qty,
                ]);
            if ($regels->isNotEmpty()) {
                return $antwoord($regels, 'goederen_in', false, $gi->created_at->toDateTimeString());
            }
        }
        // 2/3) WOW: laatste, anders de meest recente oudere upload met dit nummer
        $laatste = $this->laatsteUpload('wow');
        if (! $laatste) {
            return $uit + ['melding' => 'Nog geen WOW geüpload.'];
        }
        $uploads = \App\Models\SapUpload::where('soort', 'wow')->where('regels_opgeruimd', false)->orderByDesc('id')->pluck('created_at', 'id');
        foreach ($uploads as $id => $moment) {
            $regels = HireLine::where('upload_id', $id)->where('item_number', $nr)->orderByDesc('hire_date')->get()
                ->map(fn ($r) => [
                    'contract_number' => $r->contract_number, 'customer_name' => $r->customer_name, 'customer_number' => $r->customer_number,
                    'project_number' => $r->project_number, 'project_description' => $r->project_description, 'delivery_name' => $r->delivery_name,
                    'city' => null, 'status' => $r->status, 'depot' => $r->depot, 'hire_date' => $d($r->hire_date), 'coll_date' => $d($r->coll_date),
                    'goods_in_date' => null, 'item_description' => $r->item_description, 'qty_out' => (float) $r->qty_out,
                ]);
            if ($regels->isNotEmpty()) {
                return $antwoord($regels, 'wow', (int) $id !== (int) $laatste->id, \Carbon\Carbon::parse($moment)->toDateTimeString());
            }
        }

        return $uit + ['upload_moment' => $laatste->created_at->toDateTimeString()];
    }

    /** Dieselgedreven machine (aftanken nodig)? Op omschrijving: diesel / Stage V / aggregaat of generator — geen kabels, trafo's, tanks. */
    public static function isDiesel(?string $omschrijving): bool
    {
        $o = mb_strtolower((string) $omschrijving);
        if ($o === '' || preg_match('/kabel|cable|slang|hose|trafo|tank|verdeel|accu|lithium|230v|adapter|filter/u', $o)) {
            return false;
        }

        return (bool) preg_match('/diesel|stage\s*v\b|aggregaat|aggregat|generator/u', $o);
    }

    /**
     * Goederen-in (0.05) voor de tankapp: alle regels van de laatste upload, met dieselvlag en projectgegevens.
     * $depot = eerste 3 cijfers van het contract (leeg = alle).
     */
    public function goederenIn(?string $depot = null, bool $alleenDiesel = false): array
    {
        $gi = \App\Models\SapUpload::laatste('goederen_in');
        if (! $gi) {
            return ['upload_moment' => null, 'data' => []];
        }
        $q = \App\Models\GoodsInLine::where('upload_id', $gi->id);
        if ($depot) {
            $q->where('contract_number', 'like', $depot.'%');
        }
        $d = fn ($v) => $v ? (is_string($v) ? substr($v, 0, 10) : $v->format('Y-m-d')) : null;
        // Smartview vult einde huur / ophaaldatum met de rapportdatum zolang er niets is afgemeld → dan leeg
        $rapport = $gi->rapportdatum?->format('Y-m-d') ?? $gi->created_at->format('Y-m-d');
        $echt = fn ($v) => ($x = $d($v)) && $x !== $rapport ? $x : null;
        $rijen = $q->orderBy('goods_in_date')->get()->map(function ($r) use ($d, $echt) {
            $p = $this->projectVoorContract((string) $r->contract_number, null, null);

            return [
                'machine_number' => $r->item_number, 'omschrijving' => $r->item_description, 'diesel' => self::isDiesel($r->item_description),
                'contract_number' => $r->contract_number, 'customer_name' => $r->customer_name, 'customer_number' => $r->customer_number,
                'project_number' => $p['project_number'], 'project_description' => $p['project_description'],
                'delivery_name' => $r->delivery_name, 'city' => $r->city, 'qty' => (float) $r->qty,
                'hire_start_date' => $d($r->hire_start_date), 'hire_end_date' => $echt($r->hire_end_date),
                'collection_date' => $echt($r->collection_date), 'goods_in_date' => $d($r->goods_in_date),
                'ordered_by' => $r->ordered_by, 'created_by_code' => $r->created_by_code,
            ];
        });
        if ($alleenDiesel) {
            $rijen = $rijen->where('diesel', true);
        }

        return ['upload_moment' => $gi->created_at->toDateTimeString(), 'data' => $rijen->values()->all()];
    }

    /** Projectnummer/-omschrijving bij een contract: uit de regel zelf, anders WOW-historie, anders 8.A. Plus een projectnaam. */
    private function projectVoorContract(string $contract, ?string $nummer, ?string $omschrijving): array
    {
        static $cache = [];
        if (! $nummer && $contract !== '') {
            $cache[$contract] ??= (function () use ($contract) {
                $w = HireLine::where('contract_number', $contract)->whereNotNull('project_number')->orderByDesc('upload_id')->first(['project_number', 'project_description']);
                if ($w) {
                    return [$w->project_number, $w->project_description];
                }
                $p = \App\Models\PlanningLine::where('contract_number', $contract)->whereNotNull('project_number')->orderByDesc('upload_id')->value('project_number');

                return [$p, null];
            })();
            [$nummer, $omschrijving] = [$cache[$contract][0], $omschrijving ?: $cache[$contract][1]];
        }

        return ['project_number' => $nummer ?: null, 'project_description' => $omschrijving ?: null];
    }

    /** Uit factuurrun: per contract, met notitie, medewerker en weekhuur uit WOW. */
    public function factuurrun(array $f = []): Collection
    {
        $u = $this->laatsteUpload('factuurrun');
        if (! $u) {
            return collect();
        }
        $codes = SapUserCode::whereNotNull('employee_id')->pluck('employee_id', 'code');
        $namen = DB::table('employees')->whereIn('id', $codes->values())->pluck('name', 'id');
        $notes = InvoiceRunNote::all()->keyBy('contract_number');
        $wow = $this->laatsteUpload('wow');
        $weekhuur = $wow ? HireLine::where('upload_id', $wow->id)->selectRaw('contract_number, ROUND(SUM(weekly_hire_rate*qty_out),2) AS w')->groupBy('contract_number')->pluck('w', 'contract_number') : collect();
        $vandaag = now();
        $rijen = InvoiceRunLine::where('upload_id', $u->id)
            ->selectRaw('contract_number, MAX(customer_number) AS customer_number, MAX(customer_name) AS customer_name, MAX(created_by_code) AS created_by_code, COUNT(*) AS regels, SUM(quantity) AS aantal,
                MIN(hire_start_date) AS hire_start_date, MAX(last_invoice_date) AS last_invoice_date, MAX(delivery_name) AS delivery_name, MAX(customer_reference) AS customer_reference, MAX(ordered_by) AS ordered_by,
                SUM(CASE WHEN item_status = \'Inhuur\' THEN 1 ELSE 0 END) AS regels_inhuur, SUM(CASE WHEN item_status = \'Uit-verhuur\' THEN 1 ELSE 0 END) AS regels_uitverhuur')
            ->groupBy('contract_number')->get()
            ->map(function ($r) use ($codes, $namen, $notes, $weekhuur, $vandaag) {
                $n = $notes->get($r->contract_number);
                $r->employee_id = $r->created_by_code ? ($codes[$r->created_by_code] ?? null) : null;
                $r->employee_name = $r->employee_id ? ($namen[$r->employee_id] ?? null) : null;
                $r->weekhuur = (float) ($weekhuur[$r->contract_number] ?? 0);
                // Datums als 'Y-m-d'-string (de cast maakt er Carbon van → in JSON UTC = dag ervoor); dagen zonder factuur
                // = dagen sinds laatste factuur, anders sinds huurstart; Carbon 3 geeft een getekend verschil, dus datum → vandaag
                $dag = fn ($x) => $x instanceof \DateTimeInterface ? $x->format('Y-m-d') : ($x ? substr((string) $x, 0, 10) : null);
                $r->last_invoice_date = $dag($r->last_invoice_date);
                $r->hire_start_date = $dag($r->hire_start_date);
                $ref = $r->last_invoice_date ?: $r->hire_start_date;
                $r->dagen_zonder_factuur = $ref ? max(0, (int) \Carbon\Carbon::parse($ref)->startOfDay()->diffInDays($vandaag)) : 0;
                $r->nooit_gefactureerd = $r->last_invoice_date === null;
                $r->reden = $n?->reden;
                $r->note_status = $n?->status ?? 'te_controleren';
                $r->note_user_id = $n?->user_id;
                $r->note_updated_at = $n?->updated_at?->toDateTimeString();
                $r->eerst_gezien = $n?->eerst_gezien?->toDateString();

                return $r;
            });
        if (! empty($f['employee_id'])) {
            $rijen = $rijen->where('employee_id', (int) $f['employee_id']);
        }
        if (! empty($f['customer'])) {
            $rijen = $rijen->filter(fn ($r) => $r->customer_number === $f['customer'] || str_contains(mb_strtolower((string) $r->customer_name), mb_strtolower($f['customer'])));
        }
        if (! empty($f['status'])) {
            $rijen = $rijen->where('note_status', $f['status']);
        }
        if (! empty($f['zonder_reden'])) {
            $rijen = $rijen->filter(fn ($r) => ! $r->reden);
        }
        if (! empty($f['nooit'])) {
            $rijen = $rijen->where('nooit_gefactureerd', true);
        }

        return $rijen->sortByDesc('weekhuur')->values();
    }

    public function factuurrunContract(string $nr): ?array
    {
        $u = $this->laatsteUpload('factuurrun');
        if (! $u) {
            return null;
        }
        $regels = InvoiceRunLine::where('upload_id', $u->id)->where('contract_number', $nr)->orderBy('item_description')->get();
        if ($regels->isEmpty()) {
            return null;
        }
        $note = InvoiceRunNote::where('contract_number', $nr)->first();

        return ['contract_number' => $nr, 'kop' => $regels->first(), 'regels' => $regels, 'note' => $note, 'upload' => $this->uploadInfo($u)];
    }

    public function uploadInfo(?SapUpload $u): ?array
    {
        return $u ? ['id' => $u->id, 'soort' => $u->soort, 'moment' => $u->created_at->toDateTimeString(), 'rapportdatum' => $u->rapportdatum?->toDateString(), 'door' => $u->user?->name, 'regels' => $u->regels, 'contracten' => $u->contracten, 'uren_oud' => (int) $u->created_at->diffInHours(now()), 'samenvatting' => $u->samenvatting] : null;
    }
}
