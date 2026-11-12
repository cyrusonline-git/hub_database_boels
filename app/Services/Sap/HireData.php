<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\InvoiceRunLine;
use App\Models\InvoiceRunNote;
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
        ];
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
                $r->dagen_zonder_factuur = (int) $vandaag->diffInDays($r->last_invoice_date ? \Carbon\Carbon::parse($r->last_invoice_date) : \Carbon\Carbon::parse($r->hire_start_date ?: $vandaag));
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
