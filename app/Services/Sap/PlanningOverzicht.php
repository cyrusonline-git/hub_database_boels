<?php

namespace App\Services\Sap;

use App\Models\PlanningLine;
use App\Models\PlanningNote;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 8.7 (orders + pickstand) en 2.6 (reserveringen met regels) samengevoegd tot
 * één rij per contractnummer. Zie plan "Samenvoegregel 8.7 + 2.6".
 */
class PlanningOverzicht
{
    public function rijen(array $f = []): Collection
    {
        $p = SapUpload::laatste('planning');
        $r = SapUpload::laatste('reserveringen');
        $vandaag = now()->toDateString();
        $codes = SapUserCode::whereNotNull('employee_id')->pluck('employee_id', 'code');
        $notes = PlanningNote::all()->keyBy('order_number');

        $orders = $p ? PlanningOrder::where('upload_id', $p->id)->get()->keyBy('order_number') : collect();
        $res = $r ? ReservationLine::where('upload_id', $r->id)
            ->selectRaw('contract_number, MAX(customer_number) AS customer_number, MAX(customer_name) AS customer_name, MIN(is_contract) AS is_contract, MAX(offerte_status) AS offerte_status, MAX(contract_status) AS contract_status,
                COUNT(*) AS regels, SUM(qty) AS aantal, MIN(delivery_date) AS delivery_date, MIN(hire_start_date) AS hire_start_date, MAX(hire_end_date) AS hire_end_date, MAX(type) AS type,
                MAX(delivery_name) AS delivery_name, MAX(street) AS street, MAX(city) AS city, MAX(ordered_by) AS ordered_by, MAX(customer_reference) AS customer_reference, MAX(goods_value) AS goods_value, MAX(created_by_code) AS created_by_code')
            ->groupBy('contract_number')->get()->keyBy('contract_number') : collect();

        $pl = SapUpload::laatste('planning_regels');
        $plRegels = $pl ? PlanningLine::where('upload_id', $pl->id)->selectRaw('contract_number, COUNT(*) AS n, SUM(qty) AS aantal')->groupBy('contract_number')->get()->keyBy('contract_number') : collect();
        $nummers = $orders->keys()->merge($res->keys())->unique();
        $empIds = collect();
        $rijen = $nummers->map(function ($nr) use ($orders, $res, $codes, $notes, $vandaag, $plRegels, &$empIds) {
            $o = $orders->get($nr);
            $v = $res->get($nr);
            $isOfferte = $o === null && $v && ! (bool) $v->is_contract;
            $soort = $o?->soort ?? ($v ? (str_starts_with(mb_strtolower((string) $v->type), 'verkoop') ? 'verkoop' : 'huur') : 'overig');
            $empId = $o?->employee_id ?: ($v && $v->created_by_code ? ($codes[$v->created_by_code] ?? null) : null);
            if ($empId) {
                $empIds->push($empId);
            }
            $datum = $o?->delivery_date?->toDateString() ?? $v?->delivery_date;
            $total = $o ? (float) $o->total : null;
            $picked = $o ? (float) $o->picked : null;
            $n = $notes->get($nr);

            return (object) [
                'order_number' => (string) $nr,
                'bron' => $o && $v ? 'beide' : ($o ? 'planning' : 'reserveringen'),
                'is_offerte' => $isOfferte,
                'soort' => $isOfferte ? 'offerte' : $soort,
                'type_raw' => $o?->type_raw ?? $v?->type,
                'customer_name' => $o?->customer_name ?? $v?->customer_name,
                'customer_number' => $v?->customer_number,
                'delivery_date' => $datum,
                'te_laat' => $datum !== null && $datum < $vandaag,
                'picked' => $picked,
                'total' => $total,
                'pick_status' => $total === null ? 'onbekend' : ($total <= 0 ? 'geen_regels' : ($picked >= $total ? 'volledig' : ($picked > 0 ? 'deels' : 'niets'))),
                'contract_status' => $o?->contract_status ?? $v?->contract_status,
                'offerte_status' => $v?->offerte_status,
                'goods_value' => $v ? (float) $v->goods_value : null,
                'regels' => $v ? (int) $v->regels : (isset($plRegels[$nr]) ? (int) $plRegels[$nr]->n : null),
                'aantal' => $v ? (float) $v->aantal : (isset($plRegels[$nr]) ? (float) $plRegels[$nr]->aantal : null),
                'regels_bron' => $v ? 'reserveringen' : (isset($plRegels[$nr]) ? 'planning_regels' : null),
                'hire_start_date' => $v?->hire_start_date,
                'hire_end_date' => $v?->hire_end_date,
                'delivery_name' => $v?->delivery_name, 'street' => $v?->street, 'city' => $v?->city,
                'ordered_by' => $v?->ordered_by,
                'reference' => $o?->reference ?? $v?->customer_reference,
                'memo' => $o?->del_memo,
                'hiring_depot' => $o?->hiring_depot,
                'sales_person' => $o?->sales_person,
                'created_by_code' => $v?->created_by_code,
                'employee_id' => $empId,
                'employee_name' => null,
                'note' => $n ? ['expeditie_employee_id' => $n->expeditie_employee_id, 'opmerking' => $n->opmerking, 'vlag' => $n->vlag, 'updated_at' => $n->updated_at?->toDateTimeString()] : null,
            ];
        });
        $namen = $empIds->isEmpty() ? collect() : DB::table('employees')->whereIn('id', $empIds->unique())->pluck('name', 'id');
        $rijen = $rijen->each(fn ($x) => $x->employee_name = $x->employee_id ? ($namen[$x->employee_id] ?? null) : null);

        // Filters: standaard vandaag en later; verleden=1 = alleen op te schonen; alles=1 = alles
        if (! empty($f['verleden'])) {
            $rijen = $rijen->where('te_laat', true);
        } elseif (empty($f['alles'])) {
            $rijen = $rijen->filter(fn ($x) => $x->delivery_date === null || $x->delivery_date >= $vandaag);
        }
        if (! empty($f['van'])) {
            $rijen = $rijen->filter(fn ($x) => $x->delivery_date !== null && $x->delivery_date >= $f['van']);
        }
        if (! empty($f['tm'])) {
            $rijen = $rijen->filter(fn ($x) => $x->delivery_date !== null && $x->delivery_date <= $f['tm']);
        }
        if (! empty($f['soort'])) {
            $rijen = $rijen->whereIn('soort', (array) $f['soort']);
        }
        if (! empty($f['employee_id'])) {
            $rijen = $rijen->where('employee_id', (int) $f['employee_id']);
        }
        if (! empty($f['q'])) {
            $s = mb_strtolower($f['q']);
            $rijen = $rijen->filter(fn ($x) => str_contains(mb_strtolower($x->order_number.' '.$x->customer_name.' '.$x->reference.' '.$x->delivery_name), $s));
        }

        return $rijen->sortBy([fn ($a, $b) => strcmp((string) ($a->delivery_date ?? '9999'), (string) ($b->delivery_date ?? '9999')) ?: strcmp($a->customer_name ?? '', $b->customer_name ?? '')])->values();
    }

    public function order(string $nr): ?array
    {
        $rij = $this->rijen(['alles' => 1])->firstWhere('order_number', $nr);
        if (! $rij) {
            return null;
        }
        $r = SapUpload::laatste('reserveringen');
        $regels = $r ? ReservationLine::where('upload_id', $r->id)->where('contract_number', $nr)->orderBy('item_description')->get() : collect();
        if ($regels->isEmpty() && ($pl = SapUpload::laatste('planning_regels'))) {
            // Terugvallen op de gegroepeerde 8.7-export, in dezelfde vorm als de 2.6-regels
            $regels = PlanningLine::where('upload_id', $pl->id)->where('contract_number', $nr)->orderBy('item_description')->get()
                ->map(fn ($l) => ['item_number' => $l->item_number, 'item_description' => $l->item_description, 'qty' => $l->qty, 'type' => $l->item_status, 'delivery_date' => $l->delivery_date?->toDateString(), 'hire_end_date' => null, 'hire_end_time' => $l->delivery_time, 'memo' => $l->memo])->values();
            $rij->regels_bron = 'planning_regels';
        }

        return ['order' => $rij, 'regels' => $regels];
    }
}
