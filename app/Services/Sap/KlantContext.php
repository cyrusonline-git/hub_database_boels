<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\InvoiceRunNote;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use App\Models\InvoiceRunLine;
use Illuminate\Support\Facades\Cache;

/**
 * Smartview-beeld van één klant of één machine, voor de klantkaart en materieelpagina's in CORE
 * en voor de child-apps. Leest de laatste snapshots; 5 minuten gecached.
 */
class KlantContext
{
    public static function dashboardUrl(): string
    {
        return rtrim((string) (\App\Models\Application::where('slug', 'dashboard')->value('url') ?: 'https://dashboard.sorai.nl'), '/');
    }

    /** Alles over een klant op klantnummer (en als terugval op naam). */
    public function voorKlant(?string $nummer, ?string $naam = null): array
    {
        $sleutel = 'sap:klant:'.SapUpload::stand().':'.md5($nummer.'|'.$naam);

        return Cache::remember($sleutel, 300, function () use ($nummer, $naam) {
            $uit = ['beschikbaar' => false, 'huur' => null, 'factuurrun' => null, 'planning' => [], 'offertes' => [], 'dashboard' => self::dashboardUrl(), 'stand' => null];
            $wow = SapUpload::laatste('wow');
            if (! $wow && ! SapUpload::laatste('planning')) {
                return $uit;
            }
            $uit['beschikbaar'] = true;
            $uit['stand'] = $wow?->created_at?->toDateTimeString();
            $klantFilter = function ($q) use ($nummer, $naam) {
                $q->where(function ($w) use ($nummer, $naam) {
                    if ($nummer) {
                        $w->where('customer_number', $nummer);
                    }
                    if ($naam) {
                        $w->orWhere('customer_name', $naam);
                    }
                    if (! $nummer && ! $naam) {
                        $w->whereRaw('1 = 0');
                    }
                });
            };
            if ($wow) {
                $basis = HireLine::where('upload_id', $wow->id)->where($klantFilter);
                $contracten = (clone $basis)->selectRaw('contract_number, MAX(project_description) AS project, MAX(delivery_name) AS delivery_name, COUNT(*) AS regels, MIN(hire_date) AS sinds, ROUND(SUM(weekly_hire_rate*qty_out),2) AS weekhuur, SUM(CASE WHEN status LIKE \'%To Collect%\' THEN 1 ELSE 0 END) AS to_collect')
                    ->groupBy('contract_number')->orderByDesc('weekhuur')->get();
                $uit['huur'] = [
                    'contracten' => $contracten->count(),
                    'regels' => (int) $contracten->sum('regels'),
                    'weekhuur' => round((float) $contracten->sum('weekhuur'), 2),
                    'to_collect_contracten' => $contracten->where('to_collect', '>', 0)->count(),
                    'to_collect_regels' => (int) $contracten->sum('to_collect'),
                    'top' => $contracten->take(8)->values(),
                ];
            }
            if ($fr = SapUpload::laatste('factuurrun')) {
                $nrs = InvoiceRunLine::where('upload_id', $fr->id)->where($klantFilter)->distinct()->pluck('contract_number');
                $notes = InvoiceRunNote::whereIn('contract_number', $nrs)->get()->keyBy('contract_number');
                $uit['factuurrun'] = [
                    'contracten' => $nrs->count(),
                    'zonder_reden' => $nrs->filter(fn ($n) => ! ($notes[$n]->reden ?? null))->count(),
                    'nummers' => $nrs->values(),
                ];
            }
            if ($pl = SapUpload::laatste('planning')) {
                $uit['planning'] = PlanningOrder::where('upload_id', $pl->id)->where('customer_name', $naam ?: '__')->whereDate('delivery_date', '>=', now()->toDateString())
                    ->orderBy('delivery_date')->limit(8)->get(['order_number', 'soort', 'delivery_date', 'picked', 'total', 'contract_status'])->toArray();
            }
            if ($rs = SapUpload::laatste('reserveringen')) {
                $uit['offertes'] = ReservationLine::where('upload_id', $rs->id)->where($klantFilter)
                    ->selectRaw('contract_number, MIN(is_contract) AS is_contract, MAX(offerte_status) AS offerte_status, MIN(delivery_date) AS delivery_date, MAX(goods_value) AS goods_value, COUNT(*) AS regels')
                    ->groupBy('contract_number')->orderBy('delivery_date')->limit(10)->get()->toArray();
            }

            return $uit;
        });
    }

    /** Waar staat deze machine (artikel-/machinenummer) volgens de laatste WOW? */
    public function voorMachine(string $nummer): array
    {
        return Cache::remember('sap:machine:'.SapUpload::stand().':'.$nummer, 300, function () use ($nummer) {
            $wow = SapUpload::laatste('wow');
            if (! $wow) {
                return ['beschikbaar' => false, 'regels' => [], 'dashboard' => self::dashboardUrl()];
            }
            $regels = HireLine::where('upload_id', $wow->id)->where('item_number', $nummer)->get(['contract_number', 'customer_number', 'customer_name', 'project_description', 'delivery_name', 'delivery_address', 'hire_date', 'status', 'coll_date', 'weekly_hire_rate', 'qty_out', 'item_description']);

            return ['beschikbaar' => true, 'stand' => $wow->created_at->toDateTimeString(), 'regels' => $regels->toArray(), 'dashboard' => self::dashboardUrl()];
        });
    }
}
