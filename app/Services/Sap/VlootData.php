<?php

namespace App\Services\Sap;

use App\Models\FleetItem;
use App\Models\SapUpload;

/**
 * Vlootvoorraad (laatste upload "Items With Every Status") voor meters, werkplaats en zoeken.
 * Bezetting = verhuurd / alle machines (zoals Smartview de vloot telt, incl. onderhoud/reparatie).
 */
class VlootData
{
    public function laatste(): ?SapUpload
    {
        return SapUpload::laatste('vloot');
    }

    /** Totalen, per depot, trend (alle bewaarde samenvattingen) en desgewenst subgroepen van één depot. */
    public function stand(?string $depot = null, int $dagen = 30): array
    {
        $vl = $this->laatste();
        if (! $vl) {
            return ['upload' => null, 'melding' => 'Nog geen vlootvoorraad geüpload.'];
        }
        $depots = app(VoorraadCheck::class)->depots();
        $s = $vl->samenvatting ?? [];
        $perDepot = collect($s['per_depot'] ?? [])->map(function ($st, $d) use ($depots) {
            $tot = array_sum($st);

            return ['depot' => (string) $d, 'naam' => $depots[$d]['naam'] ?? null, 'area' => $depots[$d]['area'] ?? null, 'totaal' => $tot, 'statussen' => $st + array_fill_keys(array_keys(FleetItem::STATUSSEN), 0), 'bezetting' => $tot ? round(100 * ($st['verhuurd'] ?? 0) / $tot, 1) : 0];
        })->sortByDesc('totaal')->values();

        $trend = SapUpload::where('soort', 'vloot')->where('created_at', '>=', now()->subDays($dagen))->orderBy('id')->get()
            ->map(function ($u) use ($depot) {
                $st = $depot ? ($u->samenvatting['per_depot'][$depot] ?? []) : array_intersect_key($u->samenvatting ?? [], FleetItem::STATUSSEN);
                $tot = array_sum($st);

                return ['moment' => $u->created_at->toDateTimeString(), 'totaal' => $tot, 'verhuurd' => $st['verhuurd'] ?? 0, 'beschikbaar' => $st['beschikbaar'] ?? 0, 'onderhoud' => $st['onderhoud'] ?? 0, 'reparatie' => $st['reparatie'] ?? 0, 'onderweg' => $st['onderweg'] ?? 0, 'bezetting' => $tot ? round(100 * ($st['verhuurd'] ?? 0) / $tot, 1) : 0];
            })->filter(fn ($t) => $t['totaal'] > 0)->values();

        $totalen = array_intersect_key($s, FleetItem::STATUSSEN);
        $tot = array_sum($totalen);
        if ($depot) {
            $totalen = ($s['per_depot'][$depot] ?? []) + array_fill_keys(array_keys(FleetItem::STATUSSEN), 0);
            $tot = array_sum($totalen);
        }

        return [
            'upload' => ['id' => $vl->id, 'moment' => $vl->created_at->toDateTimeString(), 'door' => $vl->user?->name, 'uren_oud' => (int) $vl->created_at->diffInHours(now())],
            'depot' => $depot,
            'totalen' => $totalen,
            'totaal' => $tot,
            'bezetting' => $tot ? round(100 * ($totalen['verhuurd'] ?? 0) / $tot, 1) : 0,
            'per_depot' => $perDepot,
            'trend' => $trend,
            'statussen' => FleetItem::STATUSSEN,
            'overig_statussen' => $s['overig_statussen'] ?? [],
        ];
    }

    /** Subgroepen (per depot of overal) met aantallen per status en bezetting. */
    public function subgroepen(?string $depot = null, ?string $q = null, ?array $groepen = null, int $limiet = 500): array
    {
        $vl = $this->laatste();
        if (! $vl) {
            return [];
        }
        $b = FleetItem::where('upload_id', $vl->id);
        if ($depot) {
            $b->where('depot', $depot);
        }
        if ($groepen) {
            $b->whereIn('group_code', $groepen);
        }
        if ($q) {
            $b->where(fn ($w) => $w->where('group_code', $q)->orWhere('item_description', 'like', '%'.$q.'%'));
        }
        $rijen = [];
        foreach ($b->selectRaw('group_code, status_key, COUNT(*) AS n, MAX(item_description) AS oms')->groupBy('group_code', 'status_key')->get() as $r) {
            $g = (string) $r->group_code;
            $rijen[$g] ??= ['subgroep' => $g, 'omschrijving' => $r->oms, 'totaal' => 0] + array_fill_keys(array_keys(FleetItem::STATUSSEN), 0);
            $rijen[$g][$r->status_key] += (int) $r->n;
            $rijen[$g]['totaal'] += (int) $r->n;
        }

        return collect($rijen)->map(fn ($r) => $r + ['bezetting' => $r['totaal'] ? round(100 * $r['verhuurd'] / $r['totaal'], 1) : 0])
            ->sortByDesc('totaal')->take($limiet)->values()->all();
    }

    /** Eén subgroep: per depot de aantallen per status. */
    public function subgroep(string $groep): array
    {
        $vl = $this->laatste();
        if (! $vl) {
            return [];
        }
        $depots = app(VoorraadCheck::class)->depots();
        $per = [];
        $oms = null;
        foreach (FleetItem::where('upload_id', $vl->id)->where('group_code', $groep)->selectRaw('depot, status_key, COUNT(*) AS n, MAX(item_description) AS oms')->groupBy('depot', 'status_key')->get() as $r) {
            $d = (string) $r->depot;
            $per[$d] ??= ['depot' => $d, 'naam' => $depots[$d]['naam'] ?? null, 'totaal' => 0] + array_fill_keys(array_keys(FleetItem::STATUSSEN), 0);
            $per[$d][$r->status_key] += (int) $r->n;
            $per[$d]['totaal'] += (int) $r->n;
            $oms ??= $r->oms;
        }

        return ['subgroep' => $groep, 'omschrijving' => $oms, 'depots' => collect($per)->sortByDesc('beschikbaar')->values()->all()];
    }

    /** Machines van een depot in bepaalde subgroepen en statussen (werkplaats: wat na te kijken). */
    public function machines(string $depot, array $groepen, array $statussen = ['onderhoud', 'reparatie']): array
    {
        $vl = $this->laatste();
        if (! $vl || ! $groepen) {
            return [];
        }

        return FleetItem::where('upload_id', $vl->id)->where('depot', $depot)->whereIn('group_code', $groepen)->whereIn('status_key', $statussen)
            ->orderByRaw("CASE status_key WHEN 'onderhoud' THEN 0 WHEN 'reparatie' THEN 1 ELSE 2 END")->orderBy('item_number')
            ->get(['item_number', 'item_description', 'group_code', 'status', 'status_key', 'location'])->groupBy('group_code')->map->values()->all();
    }
}
