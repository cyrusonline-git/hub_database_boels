<?php

namespace App\Services\Sap;

use App\Models\FleetItem;
use App\Models\HireLine;
use App\Models\OrgDepot;
use App\Models\PlanningLine;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use Illuminate\Support\Collection;

/**
 * Voorraadcheck per contract of project — herbouw van de oude Voorraad tool (voorraad.sorai.nl),
 * regels van Wim 17-11-2026:
 *  - te zoeken = NIET TOEGEKENDE huurregels = 3.4-regels met TYPE "Groep" (groepsboeking, nog geen uniek
 *    machinenummer). Toegekend = gepickt door de expeditie → de regel wordt TYPE "Verhuur" met een machinenummer.
 *    Let op (Wim 17-11-2026): "Toegekend" in 8.A ≠ toegekend in deze zin (8.A zet ook groepsregels op Toegekend);
 *    8.A wordt alleen gebruikt voor het projectnummer. Al geleverde regels (WOW) tellen niet.
 *  - voorraad = laatste vlootupload; telt: beschikbaar (en desgewenst "in onderhoud" als tweede keus, zoals
 *    In Service in de oude tool); reparatie NOOIT (staat elders kapot);
 *  - niet-toegekende regels van ANDERE contracten en offertes binnen de horizon houden voorraad vast in hun depot
 *    (toegekende regels zijn unieke machines en staan al niet vrij); eigen contract/project telt niet mee;
 *  - eerst het eigen depot, dan de andere depots (meeste eerst); geen machine dubbel; aanvragen per subgroep + aantal;
 *  - projectnummer uit 8.A (kolom PROJECTNUMBER): alle contracten van het project samen;
 *  - transfers naar het eigen depot (8.7, alleen per order bekend) ter info.
 * Aanvragen bij collega-depots worden in de Dashboard-app bijgehouden, niet hier.
 */
class VoorraadCheck
{
    private ?array $toegekendCache = null;

    private ?array $uitstaandCache = null;

    /** Al geleverd volgens WOW: [contract][artikel] = aantal uitstaand (3.4 bevat ook al geleverde regels). */
    private function uitstaand(): array
    {
        if ($this->uitstaandCache !== null) {
            return $this->uitstaandCache;
        }
        $wow = SapUpload::laatste('wow');
        $per = [];
        if ($wow) {
            foreach (HireLine::where('upload_id', $wow->id)->selectRaw('contract_number, item_number, SUM(qty_out) AS q')->groupBy('contract_number', 'item_number')->get() as $h) {
                $per[(string) $h->contract_number][(string) $h->item_number] = (float) $h->q;
            }
        }

        return $this->uitstaandCache = $per;
    }

    public function depotUitContract(string $nr): string
    {
        return substr($nr, 0, 3);
    }

    private static function isVerkoop(?string $type): bool
    {
        return str_starts_with(mb_strtolower(trim((string) $type)), 'verkoop');
    }

    private static function isOfferte(?string $status): bool
    {
        return mb_strtolower(trim((string) $status)) === 'offerte';
    }

    /** Depotnummer → ['naam', 'email', 'area'] uit CORE Infrastructuur (alle nummers, ook aliassen). */
    public function depots(): array
    {
        $uit = [];
        foreach (OrgDepot::with('area')->get() as $d) {
            $nrs = $d->numbers();
            foreach ($nrs as $i => $nr) {
                $uit[(string) $nr] = ['nummer' => (string) $nr, 'naam' => $d->name, 'email' => $d->email, 'area' => $d->area?->name, 'alias_van' => $i > 0 ? $nrs[0] : null];
            }
        }

        return $uit;
    }

    /** Toegekende aantallen uit de laatste 8.A: [contract][artikel/subgroep] = aantal; plus projectnummers. */
    private function toegekend(): array
    {
        if ($this->toegekendCache !== null) {
            return $this->toegekendCache;
        }
        $u = SapUpload::where('soort', 'planning_regels')->orderByDesc('id')->first();
        $per = [];
        $project = [];
        if ($u) {
            foreach (PlanningLine::where('upload_id', $u->id)->get(['contract_number', 'project_number', 'item_number', 'item_status', 'qty']) as $l) {
                if ($l->item_status && mb_strtolower(trim($l->item_status)) !== 'toegekend') {
                    continue;
                }
                $c = (string) $l->contract_number;
                $per[$c][(string) $l->item_number] = ($per[$c][(string) $l->item_number] ?? 0) + (float) $l->qty;
                if ($l->project_number) {
                    $project[$c] = (string) $l->project_number;
                }
            }
        }

        return $this->toegekendCache = ['upload' => $u, 'per' => $per, 'project' => $project];
    }

    /**
     * Niet-toegekende huurregels per contract en artikel: TYPE "Groep" (nog geen machine), min al geleverd (WOW).
     * TYPE "Verhuur" = er hangt al een uniek machinenummer aan (gepickt) → telt niet.
     * Verkoop en niet-pickbare regels (brandstof, arbeid, heffingen) tellen niet.
     *
     * @return Collection<int, array{contract_number:string, item_number:string, omschrijving:?string, aantal:int, datum:?string, offerte:bool, klant:?string}>
     */
    private function openRegels(Collection $regels): Collection
    {
        $wow = $this->uitstaand();
        $uit = collect();
        foreach ($regels->filter(fn ($l) => mb_strtolower(trim((string) $l->type)) === 'groep' && ! $this->telNiet($l))->groupBy(fn ($l) => $l->contract_number.'|'.$l->item_number) as $g) {
            $f = $g->first();
            $c = (string) $f->contract_number;
            $i = (string) $f->item_number;
            $open = (int) round($g->sum('qty') - ($wow[$c][$i] ?? 0));
            if ($open <= 0) {
                continue;
            }
            $uit->push([
                'contract_number' => (string) $f->contract_number,
                'item_number' => (string) $f->item_number,
                'omschrijving' => $f->item_description,
                'aantal' => $open,
                'datum' => $g->map(fn ($l) => $this->d($l->item_delivery_date) ?? $this->d($l->delivery_date))->filter()->min(),
                'offerte' => self::isOfferte($f->contract_status),
                'klant' => $f->customer_name,
            ]);
        }

        return $uit;
    }

    private function telNiet($l): bool
    {
        return self::isVerkoop($l->type) || PlanningOverzicht::isNietPickbaar($l->item_description, $l->item_number, $l->type);
    }

    /** Artikelnummer → subgroep: een machinenummer dat in de vloot staat → zijn GRPCODE, anders is het zelf de subgroep. */
    private function subgroepVan(array $nummers, ?SapUpload $vl): array
    {
        $map = array_combine($nummers, $nummers) ?: [];
        if ($vl && $nummers) {
            foreach (array_chunk($nummers, 500) as $deel) {
                foreach (FleetItem::where('upload_id', $vl->id)->whereIn('item_number', $deel)->get(['item_number', 'group_code']) as $f) {
                    $map[(string) $f->item_number] = (string) $f->group_code;
                }
            }
        }

        return $map;
    }

    /** Contracten (en projecten) van een depot met nog niet toegekende regels, voor de keuzelijst. */
    public function contracten(?string $depot = null, ?int $employeeId = null): array
    {
        $rs = SapUpload::laatste('reserveringen');
        if (! $rs) {
            return [];
        }
        $q = ReservationLine::where('upload_id', $rs->id);
        if ($depot) {
            $q->where('contract_number', 'like', $depot.'%');
        }
        if ($employeeId) {
            $codes = \App\Models\SapUserCode::where('employee_id', $employeeId)->pluck('code')->map(fn ($c) => strtoupper($c))->all();
            $q->whereIn('created_by_code', $codes ?: ['-']);
        }
        $regels = $q->get();
        $open = $this->openRegels($regels)->groupBy('contract_number');
        $codeNaam = \App\Models\SapUserCode::with('employee')->get()->mapWithKeys(fn ($c) => [strtoupper($c->code) => $c->employee?->name])->filter();
        $projecten = $this->toegekend()['project'];
        $kop = $regels->keyBy('contract_number');

        return $open->map(function (Collection $g, $nr) use ($kop, $codeNaam, $projecten) {
            $f = $kop[$nr];

            return [
                'contract_number' => (string) $nr,
                'project_number' => $projecten[(string) $nr] ?? null,
                'customer_name' => $f->customer_name,
                'contract_status' => $f->contract_status,
                'is_offerte' => self::isOfferte($f->contract_status),
                'leverdatum' => $g->pluck('datum')->filter()->min(),
                'regels' => $g->count(),
                'stuks' => (int) $g->sum('aantal'),
                'created_by_code' => $f->created_by_code,
                'verkoper' => $codeNaam[strtoupper((string) $f->created_by_code)] ?? null,
                'aflevering' => trim(($f->delivery_name ?? '').($f->city ? ', '.$f->city : '')),
            ];
        })->sortBy(fn ($c) => ($c['leverdatum'] ?? '9999').$c['contract_number'])->values()->all();
    }

    /**
     * Voorraadcheck voor een contract- óf projectnummer.
     *
     * @param  array{depot?: ?string, horizon?: int, offertes?: bool, onderhoud?: bool}  $opties
     */
    public function contract(string $nr, array $opties = []): array
    {
        $rs = SapUpload::laatste('reserveringen');
        $vl = SapUpload::laatste('vloot');
        $pl = SapUpload::laatste('planning');
        $toeg = $this->toegekend();
        $horizon = max(1, min(120, (int) ($opties['horizon'] ?? 21)));
        $metOffertes = (bool) ($opties['offertes'] ?? true);
        $metOnderhoud = (bool) ($opties['onderhoud'] ?? false);
        $vandaag = now()->toDateString();
        $tot = now()->addDays($horizon)->toDateString();
        $depots = $this->depots();

        // Project? → alle contracten met dat projectnummer (8.A)
        $projectContracten = array_keys(array_filter($toeg['project'], fn ($p) => $p === $nr));
        $isProject = $projectContracten !== [] && ! ReservationLine::where('upload_id', $rs?->id)->where('contract_number', $nr)->exists();
        $contracten = $isProject ? $projectContracten : [$nr];
        $eigen = (string) ($opties['depot'] ?? '') ?: $this->depotUitContract($contracten[0]);

        $uit = [
            'nummer' => $nr,
            'soort' => $isProject ? 'project' : 'contract',
            'contracten' => $contracten,
            'project_number' => $isProject ? $nr : ($toeg['project'][$nr] ?? null),
            'eigen_depot' => $eigen,
            'horizon' => $horizon,
            'met_onderhoud' => $metOnderhoud,
            'stand' => [
                'contractregels' => $rs?->created_at?->toDateTimeString(),
                'toegekend' => $toeg['upload']?->created_at?->toDateTimeString(),
                'vloot' => $vl?->created_at?->toDateTimeString(),
                'planning' => $pl?->created_at?->toDateTimeString(),
            ],
            'waarschuwingen' => [],
            'depots' => $depots,
            'gevonden' => false,
        ];
        if (! $toeg['upload']) {
            $uit['waarschuwingen'][] = 'Er is nog geen 8.A geüpload: zoeken op projectnummer werkt pas na die upload.';
        }
        if ($rs && $rs->created_at->lt(now()->subHours(24))) {
            $uit['waarschuwingen'][] = 'De 3.4 (contractregels) is van '.$rs->created_at->format('d-m H:i').': regels die sindsdien gepickt of toegevoegd zijn, kloppen hier nog niet.';
        }
        if ($vl && $vl->created_at->lt(now()->subHours(24))) {
            $uit['waarschuwingen'][] = 'Vlootvoorraad is van '.$vl->created_at->format('d-m H:i').' — beschikbaarheid kan verouderd zijn.';
        }
        $orders = $pl ? PlanningOrder::where('upload_id', $pl->id)->whereIn('order_number', $contracten)->get()->keyBy('order_number') : collect();
        $uit['orders'] = $orders->map(fn ($o) => ['picked' => (int) $o->picked, 'total' => (int) $o->total, 'delivery_date' => $this->d($o->delivery_date), 'sales_person' => $o->sales_person, 'pick_up_info' => $o->pick_up_info, 'reference' => $o->reference])->all();
        $uit['transfers'] = $this->transfersNaar($eigen, $pl);
        if (! $rs) {
            $uit['melding'] = 'Er is nog geen 3.4-bestand (contractregels) geüpload in CORE.';

            return $uit;
        }
        $regels = ReservationLine::where('upload_id', $rs->id)->whereIn('contract_number', $contracten)->get();
        if ($regels->isEmpty()) {
            $uit['melding'] = $orders->isNotEmpty()
                ? 'Staat wel in de planning (8.7), maar heeft geen regels in het 3.4-bestand (niets te leveren vanaf vandaag).'
                : 'Contract/project niet gevonden in het 3.4-bestand (contractregels te leveren vanaf vandaag) of in 8.A.';

            return $uit;
        }
        $uit['gevonden'] = true;
        $uit['kop'] = $regels->groupBy('contract_number')->map(function ($g, $c) use ($toeg) {
            $f = $g->first();

            return [
                'contract_number' => (string) $c, 'project_number' => $toeg['project'][(string) $c] ?? null,
                'customer_name' => $f->customer_name, 'customer_number' => $f->customer_number, 'contract_status' => $f->contract_status,
                'is_offerte' => self::isOfferte($f->contract_status),
                'aflevering' => trim(($f->delivery_name ?? '').($f->street ? ', '.$f->street : '').($f->city ? ', '.$f->city : '')),
                'ordered_by' => $f->ordered_by, 'customer_reference' => $f->customer_reference, 'created_by_code' => $f->created_by_code,
                'leverdatum' => $g->map(fn ($l) => $this->d($l->item_delivery_date) ?? $this->d($l->delivery_date))->filter()->min(),
                'verhuurdatum' => $g->map(fn ($l) => $this->d($l->hire_start_date))->filter()->min(),
            ];
        })->values()->all();

        // Eigen open regels → per subgroep
        $eigenOpen = $this->openRegels($regels);
        $huur = $regels->reject(fn ($l) => $this->telNiet($l));
        $uit['telling'] = [
            'regels' => $regels->count(),
            'huurregels' => $huur->count(),
            'verkoop' => $regels->count() - $huur->count(),
            'stuks' => (int) round($huur->sum('qty')),
            'stuks_open' => (int) $eigenOpen->sum('aantal'),
            'stuks_toegekend_of_geleverd' => (int) round($huur->sum('qty')) - (int) $eigenOpen->sum('aantal'),
        ];
        $subVan = $this->subgroepVan($eigenOpen->pluck('item_number')->unique()->values()->all(), $vl);
        $nodig = $eigenOpen->groupBy(fn ($r) => $subVan[$r['item_number']] ?? $r['item_number'])->map(fn (Collection $g, $sub) => [
            'subgroep' => (string) $sub,
            'omschrijving' => $g->first()['omschrijving'],
            'nodig' => (int) $g->sum('aantal'),
            'leverdatum' => $g->pluck('datum')->filter()->min(),
            'contracten' => $g->pluck('contract_number')->unique()->values()->all(),
        ]);
        $subgroepen = $nodig->keys()->map(fn ($s) => (string) $s)->all();
        if (! $subgroepen) {
            $uit['subgroepen'] = [];
            $uit['per_depot'] = [];
            $uit['totaal'] = ['nodig' => 0, 'uit_eigen' => 0, 'aan_te_vragen' => 0, 'tekort' => 0];
            $uit['melding'] = 'Alle huurregels zijn al toegekend — niets te zoeken.';

            return $uit;
        }

        // Vloot per subgroep/depot/status
        $vloot = [];
        if ($vl) {
            foreach (FleetItem::where('upload_id', $vl->id)->whereIn('group_code', $subgroepen)
                ->selectRaw('group_code, depot, status_key, COUNT(*) AS n')->groupBy('group_code', 'depot', 'status_key')->get() as $r) {
                $vloot[(string) $r->group_code][(string) $r->depot][$r->status_key] = (int) $r->n;
            }
        } else {
            $uit['melding'] = 'Er is nog geen vlootvoorraad geüpload in CORE — beschikbaarheid onbekend.';
        }

        // Claims: niet-toegekende regels van ANDERE contracten/offertes binnen de horizon, in het depot van dat contract
        $anderen = ReservationLine::where('upload_id', $rs->id)->whereNotIn('contract_number', $contracten)->get();
        $anderOpen = $this->openRegels($anderen);
        $subAnder = $this->subgroepVan($anderOpen->pluck('item_number')->unique()->values()->all(), $vl);
        $claimPer = [];
        foreach ($anderOpen as $r) {
            $sub = $subAnder[$r['item_number']] ?? $r['item_number'];
            if (! isset($nodig[$sub])) {
                continue;
            }
            $d = $r['datum'];
            if ($r['offerte'] ? ! ($metOffertes && $d && $d >= $vandaag && $d <= $tot) : ($d && $d > $tot)) {
                continue;   // offertes alleen binnen de horizon; contracten t/m de horizon (ook achterstallige)
            }
            $claimPer[$sub][$this->depotUitContract($r['contract_number'])][] = ['contract_number' => $r['contract_number'], 'klant' => $r['klant'], 'aantal' => $r['aantal'], 'datum' => $d, 'offerte' => $r['offerte']];
        }

        $rijen = [];
        $perDepot = [];
        foreach ($nodig->sortBy('subgroep') as $sub => $n) {
            $sub = (string) $sub;
            $v = $vloot[$sub] ?? [];
            $vrij = [];          // [depot][status] na aftrek reserveringen (eerst van beschikbaar, dan onderhoud)
            $gereserveerd = [];
            foreach ($v as $d => $st) {
                $vast = array_sum(array_column($claimPer[$sub][$d] ?? [], 'aantal'));
                $gereserveerd[$d] = $vast;
                $b = $st['beschikbaar'] ?? 0;
                $o = $metOnderhoud ? ($st['onderhoud'] ?? 0) : 0;
                $afB = min($vast, $b);
                $afO = min($vast - $afB, $o);
                $vrij[$d] = ['beschikbaar' => $b - $afB, 'onderhoud' => $o - $afO];
            }
            // Stappen zoals de oude tool (zonder reparatie)
            $stappen = [['eigen', 'beschikbaar'], ['ander', 'beschikbaar']];
            if ($metOnderhoud) {
                $stappen = [['eigen', 'beschikbaar'], ['eigen', 'onderhoud'], ['ander', 'beschikbaar'], ['ander', 'onderhoud']];
            }
            $rest = $n['nodig'];
            $uitEigen = ['beschikbaar' => 0, 'onderhoud' => 0];
            $advies = [];
            foreach ($stappen as [$wie, $status]) {
                if ($rest <= 0) {
                    break;
                }
                $kandidaten = [];
                foreach ($vrij as $d => $x) {
                    if (($wie === 'eigen') === ((string) $d === $eigen) && $x[$status] > 0) {
                        $kandidaten[(string) $d] = $x[$status];
                    }
                }
                arsort($kandidaten);
                foreach ($kandidaten as $d => $x) {
                    if ($rest <= 0) {
                        break;
                    }
                    $neem = min($rest, $x);
                    $rest -= $neem;
                    $vrij[$d][$status] -= $neem;
                    if ($wie === 'eigen') {
                        $uitEigen[$status] += $neem;
                    } else {
                        $advies[$d][$status] = ($advies[$d][$status] ?? 0) + $neem;
                    }
                }
            }
            foreach ($advies as $d => $a) {
                $perDepot[$d][] = ['subgroep' => $sub, 'omschrijving' => $n['omschrijving'], 'aantal' => array_sum($a), 'beschikbaar' => $a['beschikbaar'] ?? 0, 'onderhoud' => $a['onderhoud'] ?? 0, 'vrij_beschikbaar' => max(0, ($v[$d]['beschikbaar'] ?? 0) - ($gereserveerd[$d] ?? 0))];
            }
            $vrijPerDepot = [];
            $onderhoudPerDepot = [];
            foreach ($v as $d => $st) {
                if (($x = max(0, ($st['beschikbaar'] ?? 0) - ($gereserveerd[$d] ?? 0))) > 0) {
                    $vrijPerDepot[(string) $d] = $x;
                }
                if (($st['onderhoud'] ?? 0) > 0) {
                    $onderhoudPerDepot[(string) $d] = $st['onderhoud'];
                }
            }
            $rijen[] = $n + [
                'eigen' => [
                    'beschikbaar' => $v[$eigen]['beschikbaar'] ?? 0, 'onderhoud' => $v[$eigen]['onderhoud'] ?? 0,
                    'onderweg' => $v[$eigen]['onderweg'] ?? 0, 'gereserveerd' => $gereserveerd[$eigen] ?? 0,
                ],
                'uit_eigen' => array_sum($uitEigen),
                'uit_eigen_onderhoud' => $uitEigen['onderhoud'],
                'advies' => array_map(fn ($a) => array_sum($a), $advies),
                'advies_onderhoud' => array_filter(array_map(fn ($a) => $a['onderhoud'] ?? 0, $advies)),
                'tekort' => $rest,
                'beschikbaar_per_depot' => $vrijPerDepot,
                'onderhoud_per_depot' => $onderhoudPerDepot,
                'reserveringen' => collect($claimPer[$sub] ?? [])->flatten(1)->sortBy('datum')->values()->all(),
                'in_vloot' => isset($vloot[$sub]),
            ];
        }
        $uit['subgroepen'] = $rijen;
        $uit['per_depot'] = collect($perDepot)->map(fn ($r, $d) => ['depot' => (string) $d, 'naam' => $depots[$d]['naam'] ?? null, 'email' => $depots[$d]['email'] ?? null, 'stuks' => array_sum(array_column($r, 'aantal')), 'regels' => $r])
            ->sortByDesc('stuks')->values()->all();
        $uit['totaal'] = [
            'nodig' => array_sum(array_column($rijen, 'nodig')),
            'uit_eigen' => array_sum(array_column($rijen, 'uit_eigen')),
            'aan_te_vragen' => array_sum(array_map(fn ($r) => array_sum($r['advies']), $rijen)),
            'tekort' => array_sum(array_column($rijen, 'tekort')),
        ];

        return $uit;
    }

    /**
     * Open vraag per subgroep voor een depot: niet-toegekende regels van contracten (en desgewenst offertes) van dat
     * depot met leverdatum t/m vandaag + horizon (achterstallige contracten tellen ook). Voor de werkplaatslijst.
     */
    public function vraag(string $depot, int $horizon = 14, bool $metOffertes = true): array
    {
        $rs = SapUpload::laatste('reserveringen');
        if (! $rs) {
            return [];
        }
        $tot = now()->addDays($horizon)->toDateString();
        $vandaag = now()->toDateString();
        $open = $this->openRegels(ReservationLine::where('upload_id', $rs->id)->where('contract_number', 'like', $depot.'%')->get())
            ->filter(fn ($r) => $r['offerte'] ? ($metOffertes && $r['datum'] && $r['datum'] >= $vandaag && $r['datum'] <= $tot) : (! $r['datum'] || $r['datum'] <= $tot));
        $sub = $this->subgroepVan($open->pluck('item_number')->unique()->values()->all(), SapUpload::laatste('vloot'));

        return $open->groupBy(fn ($r) => $sub[$r['item_number']] ?? $r['item_number'])->map(fn ($g, $s) => [
            'subgroep' => (string) $s,
            'omschrijving' => $g->first()['omschrijving'],
            'aantal' => (int) $g->sum('aantal'),
            'eerste_datum' => $g->pluck('datum')->filter()->min(),
            'contracten' => $g->map(fn ($r) => ['contract_number' => $r['contract_number'], 'klant' => $r['klant'], 'aantal' => $r['aantal'], 'datum' => $r['datum'], 'offerte' => $r['offerte']])->values()->all(),
        ])->sortBy('eerste_datum')->values()->all();
    }

    /** Depot Transfer In naar dit depot (8.7: alleen per order, zonder artikelregels). */
    private function transfersNaar(string $depot, ?SapUpload $pl): array
    {
        if (! $pl) {
            return [];
        }

        return PlanningOrder::where('upload_id', $pl->id)->where('soort', 'transfer_in')->where('depot', $depot)->get()
            ->map(fn ($o) => ['order_number' => $o->order_number, 'van' => $o->hiring_depot, 'picked' => (int) $o->picked, 'total' => (int) $o->total, 'delivery_date' => $this->d($o->delivery_date), 'reference' => $o->reference, 'status' => $o->contract_status])
            ->sortBy('delivery_date')->values()->all();
    }

    private function d(mixed $v): ?string
    {
        if (! $v) {
            return null;
        }

        return $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : substr((string) $v, 0, 10);
    }
}
