<?php

namespace App\Services\Sap;

use App\Models\FleetItem;
use App\Models\HireLine;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Stockplanner (Wim 19-11-2026, naar voorbeeld van de Qlik "Industrial and Power & HVAC stock planner"):
 * per depot × subgroep de verwachte voorraad per dag.
 *
 *  start   = beschikbaar in de laatste vlootupload (optioneel + in onderhoud)
 *  −       = nog niet gepickte contractregels (3.4 TYPE Groep, min WOW) op hun leverdatum
 *  −       = offertes (optioneel) op hun leverdatum, alleen vanaf vandaag (verlopen offertes niet)
 *  −       = gepickte maar nog niet geleverde machines (3.4 TYPE Verhuur) die in de vloot nog op beschikbaar staan
 *  +       = goederen-in (0.05): retour gekomen machines die de vloot nog als verhuurd ziet, op goederen-in-datum +
 *            dezelfde verwerkingstijd (in onderhoud/beschikbaar staan ze al in de vloot → niet dubbel)
 *  +       = lopende huur (WOW) met bekende einddatum (ophaaldatum, anders verwachte retourdatum) + instelbare
 *            verwerkingstijd ("einde huur als": einddatum / +3 (standaard) / +7 / +14 dagen)
 *
 * Het depot van een mutatie komt uit het contractnummer (eerste 3 cijfers) of uit de WOW-kolom depot, nooit uit een
 * vaste waarde: zodra de uploads alle depots bevatten (config sap.depot leeg), rekent de planner voor alle depots.
 */
class StockPlanner
{
    public function __construct(private VoorraadCheck $check) {}

    public const TYPES = [
        'levering' => 'Levering contract',
        'offerte' => 'Offerte',
        'gepickt' => 'Gepickt, nog te leveren',
        'retour' => 'Retour einde huur',
        'goederen_in' => 'Retour (goederen-in)',
    ];

    /** Alle mutaties (alle depots) — 5 min gecachet per uploadstand en opties. */
    public function mutaties(int $retourDagen, bool $offertes): Collection
    {
        $sleutel = 'stockplanner:mut:'.SapUpload::stand().":$retourDagen:".(int) $offertes;

        return collect(Cache::remember($sleutel, 300, function () use ($retourDagen, $offertes) {
            $rs = SapUpload::laatste('reserveringen');
            $vl = SapUpload::laatste('vloot');
            $wow = SapUpload::laatste('wow');
            $vandaag = now()->toDateString();
            $uit = [];

            if ($rs) {
                $regels = ReservationLine::where('upload_id', $rs->id)->get();
                // 1) Nog niet gepickt (Groep) — contracten en offertes
                $open = $this->check->openRegels($regels);
                $sub = $this->check->subgroepVan($open->pluck('item_number')->unique()->values()->all(), $vl);
                foreach ($open as $r) {
                    if ($r['offerte'] && (! $offertes || ($r['datum'] && $r['datum'] < $vandaag))) {
                        continue;   // offertes alleen vanaf vandaag (verlopen offertes tellen niet, zoals in de voorraadcheck)
                    }
                    $uit[] = [
                        'depot' => $this->check->depotUitContract($r['contract_number']),
                        'subgroep' => (string) ($sub[$r['item_number']] ?? $r['item_number']),
                        'datum' => max($vandaag, $r['datum'] ?? $vandaag),   // achterstallig = vandaag nodig
                        'aantal' => -$r['aantal'],
                        'type' => $r['offerte'] ? 'offerte' : 'levering',
                        'contract' => $r['contract_number'],
                        'klant' => $r['klant'],
                        'omschrijving' => $r['omschrijving'],
                        'achterstallig' => $r['datum'] && $r['datum'] < $vandaag,
                    ];
                }
                // 2) Gepickt (Verhuur, machinenummer) maar nog niet geleverd én in de vloot nog beschikbaar
                if ($vl) {
                    $verhuur = $regels->filter(fn ($l) => mb_strtolower(trim((string) $l->type)) === 'verhuur');
                    $machines = FleetItem::where('upload_id', $vl->id)->whereIn('item_number', $verhuur->pluck('item_number')->unique()->values()->all())
                        ->where('status_key', 'beschikbaar')->get(['item_number', 'group_code', 'depot'])->keyBy('item_number');
                    $geleverd = $wow ? HireLine::where('upload_id', $wow->id)->whereIn('item_number', $machines->keys()->all())->pluck('item_number')->flip() : collect();
                    foreach ($verhuur as $l) {
                        $m = $machines[(string) $l->item_number] ?? null;
                        if (! $m || isset($geleverd[(string) $l->item_number])) {
                            continue;
                        }
                        $d = $this->d($l->item_delivery_date) ?? $this->d($l->delivery_date) ?? $vandaag;
                        $uit[] = [
                            'depot' => (string) $m->depot, 'subgroep' => (string) $m->group_code, 'datum' => max($vandaag, $d),
                            'aantal' => -1, 'type' => 'gepickt', 'contract' => (string) $l->contract_number, 'klant' => $l->customer_name,
                            'omschrijving' => $l->item_description, 'achterstallig' => $d < $vandaag,
                        ];
                    }
                }
            }

            // 3a) Goederen-in (0.05): retour gekomen, vloot ziet ze nog als verhuurd (of kent ze niet) → komen terug
            $goederenIn = [];
            $gi = SapUpload::laatste('goederen_in');
            if ($gi) {
                $regels = \App\Models\GoodsInLine::where('upload_id', $gi->id)->get();
                $status = $vl ? FleetItem::where('upload_id', $vl->id)->whereIn('item_number', $regels->pluck('item_number')->unique()->values()->all())
                    ->get(['item_number', 'status_key', 'group_code'])->keyBy('item_number') : collect();
                $sub = $this->check->subgroepVan($regels->pluck('item_number')->map(fn ($x) => (string) $x)->unique()->values()->all(), $vl);
                foreach ($regels as $g) {
                    $nr = (string) $g->item_number;
                    $goederenIn[$nr] = true;
                    $st = $status[$nr]->status_key ?? null;
                    if ($st !== null && $st !== 'verhuurd') {
                        continue;   // staat al als onderhoud/beschikbaar/reparatie in de vloot
                    }
                    $in = $g->goods_in_date ? \Carbon\Carbon::parse($g->goods_in_date)->addDays($retourDagen)->toDateString() : $vandaag;
                    $uit[] = [
                        'depot' => $this->check->depotUitContract((string) $g->contract_number),
                        'subgroep' => (string) ($sub[$nr] ?? $nr),
                        'datum' => max($vandaag, $in),
                        'aantal' => (int) round((float) $g->qty ?: 1),
                        'type' => 'goederen_in', 'contract' => (string) $g->contract_number, 'klant' => $g->customer_name,
                        'omschrijving' => $g->item_description, 'achterstallig' => $in < $vandaag,
                    ];
                }
            }

            // 3) Retouren: lopende huur met bekende einddatum (niet als de machine al in goederen-in staat)
            if ($wow) {
                $hl = HireLine::where('upload_id', $wow->id)->where(fn ($q) => $q->whereNotNull('coll_date')->orWhereNotNull('est_return_date'))
                    ->get(['depot', 'contract_number', 'customer_name', 'item_number', 'item_description', 'qty_out', 'coll_date', 'est_return_date', 'status']);
                $sub = $this->check->subgroepVan($hl->pluck('item_number')->map(fn ($x) => (string) $x)->unique()->values()->all(), $vl);
                foreach ($hl as $h) {
                    if (isset($goederenIn[(string) $h->item_number])) {
                        continue;
                    }
                    $eind = $this->d($h->coll_date) ?? $this->d($h->est_return_date);
                    if (! $eind) {
                        continue;
                    }
                    $terug = \Carbon\Carbon::parse($eind)->addDays($retourDagen)->toDateString();
                    $uit[] = [
                        'depot' => (string) ($h->depot ?: $this->check->depotUitContract((string) $h->contract_number)),
                        'subgroep' => (string) ($sub[(string) $h->item_number] ?? $h->item_number),
                        'datum' => max($vandaag, $terug),
                        'aantal' => (int) round((float) $h->qty_out ?: 1),
                        'type' => 'retour', 'contract' => (string) $h->contract_number, 'klant' => $h->customer_name,
                        'omschrijving' => $h->item_description, 'achterstallig' => $terug < $vandaag,
                    ];
                }
            }

            return $uit;
        }));
    }

    /** Startvoorraad per depot/subgroep uit de vloot: ['depot']['subgroep'] = [beschikbaar, onderhoud, omschrijving]. */
    public function start(?string $depot = null): array
    {
        $vl = SapUpload::laatste('vloot');
        if (! $vl) {
            return [];
        }

        return Cache::remember('stockplanner:start:'.$vl->id.':'.($depot ?? '*'), 300, function () use ($vl, $depot) {
            $q = FleetItem::where('upload_id', $vl->id)->whereIn('status_key', ['beschikbaar', 'onderhoud', 'verhuurd', 'reparatie']);
            if ($depot) {
                $q->where('depot', $depot);
            }
            $uit = [];
            foreach ($q->selectRaw('depot, group_code, status_key, COUNT(*) AS n, MAX(item_description) AS oms')->groupBy('depot', 'group_code', 'status_key')->get() as $r) {
                $x = &$uit[(string) $r->depot][(string) $r->group_code];
                $x ??= ['beschikbaar' => 0, 'onderhoud' => 0, 'verhuurd' => 0, 'reparatie' => 0, 'omschrijving' => $r->oms];
                $x[$r->status_key] = (int) $r->n;
                unset($x);
            }

            return $uit;
        });
    }

    /** Alle subgroepen die ergens in de vloot voorkomen (welk depot of status dan ook). */
    public function inVloot(): array
    {
        $vl = SapUpload::laatste('vloot');

        return $vl ? Cache::remember('stockplanner:invloot:'.$vl->id, 300, fn () => FleetItem::where('upload_id', $vl->id)->distinct()->pluck('group_code')->map(fn ($g) => (string) $g)->flip()->all()) : [];
    }

    /**
     * Matrix voor één depot: rijen = subgroepen, kolommen = dagen of weken.
     *
     * @param  array{horizon?:int, retour_dagen?:int, offertes?:bool, onderhoud?:bool, q?:?string, alleen_tekort?:bool, alleen_mutaties?:bool, per?:string}  $o
     */
    public function matrix(string $depot, array $o = []): array
    {
        $horizon = max(7, min(180, (int) ($o['horizon'] ?? 28)));
        $retour = (int) ($o['retour_dagen'] ?? 3);
        $metOnderhoud = (bool) ($o['onderhoud'] ?? false);
        $metReparatie = (bool) ($o['reparatie'] ?? false);
        $perWeek = ($o['per'] ?? 'dag') === 'week';
        $q = mb_strtolower(trim((string) ($o['q'] ?? '')));
        $kolommen = $this->kolommen($horizon, $perWeek);
        $laatste = end($kolommen)['tot'];

        $start = $this->start($depot)[$depot] ?? [];
        $mut = $this->mutaties($retour, (bool) ($o['offertes'] ?? true))->where('depot', $depot)->filter(fn ($m) => $m['datum'] <= $laatste);
        $perSub = $mut->groupBy('subgroep');
        $subgroepen = collect(array_keys($start))->merge($perSub->keys())->map(fn ($s) => (string) $s)->unique();
        $inVloot = $this->inVloot();
        $zonderVloot = 0;

        $rijen = [];
        foreach ($subgroepen as $sub) {
            $st = ($start[$sub] ?? []) + ['beschikbaar' => 0, 'onderhoud' => 0, 'verhuurd' => 0, 'reparatie' => 0, 'omschrijving' => null];
            $m = $perSub[$sub] ?? collect();
            if (! empty($o['alleen_mutaties']) && $m->isEmpty()) {
                continue;
            }
            $oms = $st['omschrijving'] ?? $m->first()['omschrijving'] ?? null;
            if ($q !== '' && ! str_contains(mb_strtolower($sub.' '.$oms), $q)) {
                continue;
            }
            $bekend = isset($inVloot[$sub]);
            if (! $bekend && empty($o['zonder_vloot'])) {
                $zonderVloot++;   // bulk-/inhuur-/verkoopartikel zonder vlootgegevens: standaard verbergen
                continue;
            }
            $stand = $st['beschikbaar'] + ($metOnderhoud ? $st['onderhoud'] : 0) + ($metReparatie ? $st['reparatie'] : 0);
            $reeks = [];
            $min = $stand;
            $eersteTekort = null;
            foreach ($kolommen as $k) {
                $delta = $m->filter(fn ($x) => $x['datum'] >= $k['van'] && $x['datum'] <= $k['tot']);
                // binnen een week telt het laagste punt: eerst alles eraf wat die week vertrekt, retouren pas na de week
                $eraf = $delta->where('aantal', '<', 0)->sum('aantal');
                $erbij = $delta->where('aantal', '>', 0)->sum('aantal');
                $laag = $stand + ($perWeek ? $eraf : $eraf + $erbij);
                $stand += $eraf + $erbij;
                $waarde = $perWeek ? min($laag, $stand) : $stand;
                $reeks[] = $waarde;
                if ($waarde < $min) {
                    $min = $waarde;
                }
                if ($waarde < 0 && ! $eersteTekort) {
                    $eersteTekort = $k['van'];
                }
            }
            if (! empty($o['alleen_tekort']) && $min >= 0) {
                continue;
            }
            $rijen[] = [
                'subgroep' => $sub, 'omschrijving' => $oms,
                'beschikbaar' => $st['beschikbaar'], 'onderhoud' => $st['onderhoud'], 'verhuurd' => $st['verhuurd'], 'reparatie' => $st['reparatie'],
                'start' => $st['beschikbaar'] + ($metOnderhoud ? $st['onderhoud'] : 0) + ($metReparatie ? $st['reparatie'] : 0),
                'uit' => (int) -$m->where('aantal', '<', 0)->sum('aantal'), 'in' => (int) $m->where('aantal', '>', 0)->sum('aantal'),
                'reeks' => $reeks, 'min' => $min, 'eerste_tekort' => $eersteTekort, 'in_vloot' => $bekend,
            ];
        }
        usort($rijen, fn ($a, $b) => [$a['min'] >= 0, $a['eerste_tekort'] ?? '9999', $a['min'], $b['uit']] <=> [$b['min'] >= 0, $b['eerste_tekort'] ?? '9999', $b['min'], $a['uit']]);

        return [
            'depot' => $depot,
            'kolommen' => $kolommen,
            'rijen' => array_slice($rijen, 0, (int) ($o['limiet'] ?? 400)),
            'aantal' => count($rijen),
            'tekorten' => count(array_filter($rijen, fn ($r) => $r['min'] < 0)),
            'zonder_vloot' => $zonderVloot,
            'depots_met_mutaties' => $this->mutaties($retour, (bool) ($o['offertes'] ?? true))->pluck('depot')->unique()->sort()->values()->all(),
            'stand' => $this->stand(),
        ];
    }

    /** Eén subgroep: tijdlijn per depot (Planner alle depots) + de mutaties in het gekozen depot. */
    public function subgroep(string $sub, ?string $depot, array $o = []): array
    {
        $horizon = max(7, min(180, (int) ($o['horizon'] ?? 28)));
        $retour = (int) ($o['retour_dagen'] ?? 3);
        $metOnderhoud = (bool) ($o['onderhoud'] ?? false);
        $metReparatie = (bool) ($o['reparatie'] ?? false);
        $kolommen = $this->kolommen($horizon, false);
        $laatste = end($kolommen)['tot'];
        $start = $this->start();
        $mut = $this->mutaties($retour, (bool) ($o['offertes'] ?? true))->where('subgroep', $sub)->filter(fn ($m) => $m['datum'] <= $laatste);
        $depots = app(VoorraadCheck::class)->depots();
        $alle = collect(array_keys($start))->filter(fn ($d) => isset($start[$d][$sub]))->merge($mut->pluck('depot'))->map(fn ($d) => (string) $d)->unique();

        $perDepot = [];
        $oms = null;
        foreach ($alle as $d) {
            $st = ($start[$d][$sub] ?? []) + ['beschikbaar' => 0, 'onderhoud' => 0, 'verhuurd' => 0, 'reparatie' => 0, 'omschrijving' => null];
            $oms ??= $st['omschrijving'];
            $stand = $st['beschikbaar'] + ($metOnderhoud ? $st['onderhoud'] : 0) + ($metReparatie ? $st['reparatie'] : 0);
            $md = $mut->where('depot', $d);
            $reeks = [];
            foreach ($kolommen as $k) {
                $stand += $md->filter(fn ($x) => $x['datum'] === $k['van'])->sum('aantal');
                $reeks[] = $stand;
            }
            $perDepot[] = ['depot' => $d, 'naam' => $depots[$d]['naam'] ?? null, 'beschikbaar' => $st['beschikbaar'], 'onderhoud' => $st['onderhoud'], 'verhuurd' => $st['verhuurd'], 'reparatie' => $st['reparatie'], 'reeks' => $reeks, 'min' => $reeks ? min($reeks) : 0, 'mutaties' => $md->count()];
        }
        usort($perDepot, fn ($a, $b) => [$b['depot'] === $depot, $b['beschikbaar']] <=> [$a['depot'] === $depot, $a['beschikbaar']]);

        return [
            'subgroep' => $sub,
            'omschrijving' => $oms ?? $mut->first()['omschrijving'] ?? null,
            'depot' => $depot,
            'kolommen' => $kolommen,
            'per_depot' => $perDepot,
            'mutaties' => $mut->when($depot, fn ($c) => $c->where('depot', $depot))->sortBy('datum')->values()->all(),
            'types' => self::TYPES,
        ];
    }

    /** Welke uploads de planner gebruikt (en of ze alle depots bevatten). */
    public function stand(): array
    {
        $uit = [];
        foreach (['vloot' => 'vloot', 'reserveringen' => 'contractregels (3.4)', 'wow' => 'lopende huur (WOW)'] as $s => $label) {
            $u = SapUpload::laatste($s);
            $uit[$s] = ['label' => $label, 'moment' => $u?->created_at?->toDateTimeString()];
        }
        $uit['alleen_depot'] = (string) config('sap.depot', '') ?: null;   // null = uploads voor alle depots

        return $uit;
    }

    private function kolommen(int $horizon, bool $perWeek): array
    {
        $k = [];
        $d = now()->startOfDay();
        $eind = now()->addDays($horizon)->startOfDay();
        if ($perWeek) {
            $d = $d->copy();
            while ($d <= $eind) {
                $tot = $d->copy()->endOfWeek()->startOfDay();
                $k[] = ['van' => $d->toDateString(), 'tot' => $tot->toDateString(), 'label' => 'wk '.$d->isoWeek(), 'sub' => $d->format('d-m')];
                $d = $tot->copy()->addDay();
            }
        } else {
            for ($i = 0; $i <= $horizon; $i++) {
                $x = now()->addDays($i);
                $k[] = ['van' => $x->toDateString(), 'tot' => $x->toDateString(), 'label' => $x->format('d-m'), 'sub' => mb_substr($x->locale('nl')->dayName, 0, 2), 'weekend' => $x->isWeekend()];
            }
        }

        return $k;
    }

    private function d(mixed $v): ?string
    {
        if (! $v) {
            return null;
        }

        return $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : substr((string) $v, 0, 10);
    }
}
