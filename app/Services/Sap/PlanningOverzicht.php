<?php

namespace App\Services\Sap;

use App\Models\HireLine;
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
        // 8.7 kolom B (PickUpInfo) leeg = 'profiel niet ingevuld': de expeditie ziet de order niet in InspHire Mobile en pickt hem niet (Wim 13-11-2026)
        $pickinfoBekend = $p && ! empty($p->samenvatting['pickinfo_bekend']);
        $res = $r ? ReservationLine::where('upload_id', $r->id)
            ->selectRaw('contract_number, MAX(customer_number) AS customer_number, MAX(customer_name) AS customer_name, MIN(is_contract) AS is_contract, MAX(is_bijhuur) AS is_bijhuur, MAX(offerte_status) AS offerte_status, MAX(contract_status) AS contract_status,
                COUNT(*) AS regels, SUM(qty) AS aantal, SUM(CASE WHEN item_number = \'44331\' OR LOWER(item_description) LIKE \'%per liter%\' OR LOWER(item_description) LIKE \'%per litre%\' OR LOWER(item_description) LIKE \'%retourname%\' OR LOWER(item_description) LIKE \'%on return%\' OR LOWER(item_description) LIKE \'%arbeid%\' OR LOWER(item_description) LIKE \'%per uur%\' OR LOWER(item_description) LIKE \'%heffing%\' OR LOWER(item_description) LIKE \'%toeslag%\' THEN 1 ELSE 0 END) AS niet_pickbaar, COALESCE(MIN(item_delivery_date), MIN(delivery_date)) AS delivery_date, MIN(hire_start_date) AS hire_start_date, MAX(hire_end_date) AS hire_end_date, MAX(type) AS type,
                MAX(delivery_name) AS delivery_name, MAX(street) AS street, MAX(city) AS city, MAX(ordered_by) AS ordered_by, MAX(customer_reference) AS customer_reference, MAX(goods_value) AS goods_value, MAX(created_by_code) AS created_by_code')
            ->groupBy('contract_number')->get()->keyBy('contract_number') : collect();

        $pl = SapUpload::laatste('planning_regels');
        $plRegels = $pl ? PlanningLine::where('upload_id', $pl->id)->selectRaw('contract_number, COUNT(*) AS n, SUM(qty) AS aantal')->groupBy('contract_number')->get()->keyBy('contract_number') : collect();
        // Bijhuur: order op een contract dat volgens WOW al (deels) in huur is
        $wow = SapUpload::laatste('wow');
        $inHuur = $wow ? HireLine::where('upload_id', $wow->id)->selectRaw('contract_number, COUNT(*) AS n, MAX(hire_date) AS laatste, MIN(hire_date) AS eerste, MAX(customer_name) AS klant')->groupBy('contract_number')->get()->keyBy('contract_number') : collect();
        // Per bijhuur-contract: welke regels van de gegroepeerde 8.7 staan nog NIET in huur (= te leveren) en op welke datum
        $teLeveren = [];
        // 1) Uit 3.4 (reservation_lines met item_delivery_date): nieuwe regels op lopende contracten (bijhuur) minus wat al in huur staat
        if ($wow && $r) {
            // Alleen contracten die volgens WOW al (deels) in huur staan; 3.4-status 'Inhuur' alleen zegt niets
            // (ook een vandaag volledig gepickte nieuwe order krijgt die status)
            $bijhuurNrs34 = $res->keys()->filter(fn ($nr) => isset($inHuur[$nr]))->values();
            if ($bijhuurNrs34->isNotEmpty()) {
                $uitstaand = HireLine::where('upload_id', $wow->id)->whereIn('contract_number', $bijhuurNrs34)->selectRaw('contract_number, item_number, SUM(qty_out) AS q')->groupBy('contract_number', 'item_number')->get()
                    ->groupBy('contract_number')->map(fn ($g) => $g->pluck('q', 'item_number'));
                foreach (ReservationLine::where('upload_id', $r->id)->whereIn('contract_number', $bijhuurNrs34)->get() as $l) {
                    if (self::isNietPickbaar($l->item_description, $l->item_number, $l->type)) {
                        continue;   // brandstof, arbeid, heffing = niets te picken
                    }
                    $al = (float) ($uitstaand[$l->contract_number][$l->item_number] ?? 0);
                    $rest = (float) $l->qty - $al;
                    if ($rest > 0) {
                        $teLeveren[$l->contract_number][] = ['item_number' => $l->item_number, 'item_description' => $l->item_description, 'qty' => $rest, 'delivery_date' => $l->item_delivery_date?->toDateString() ?? $l->delivery_date?->toDateString(), 'delivery_time' => null, 'memo' => null];
                    }
                }
            }
        }
        // 2) Terugval: gegroepeerde 8.7/8.A (planning_lines), alleen voor contracten zonder 3.4-regels
        if ($wow && ($pl = SapUpload::laatste('planning_regels'))) {
            $bijhuurNrs = $orders->keys()->filter(fn ($nr) => isset($inHuur[$nr]) && empty($teLeveren[$nr]))->values();
            if ($bijhuurNrs->isNotEmpty()) {
                $uitstaand = HireLine::where('upload_id', $wow->id)->whereIn('contract_number', $bijhuurNrs)->selectRaw('contract_number, item_number, SUM(qty_out) AS q')->groupBy('contract_number', 'item_number')->get()
                    ->groupBy('contract_number')->map(fn ($g) => $g->pluck('q', 'item_number'));
                foreach (PlanningLine::where('upload_id', $pl->id)->whereIn('contract_number', $bijhuurNrs)->get() as $l) {
                    if (self::isNietPickbaar($l->item_description, $l->item_number)) {
                        continue;
                    }
                    $al = (float) ($uitstaand[$l->contract_number][$l->item_number] ?? 0);
                    $rest = (float) $l->qty - $al;
                    if ($rest > 0) {
                        $teLeveren[$l->contract_number][] = ['item_number' => $l->item_number, 'item_description' => $l->item_description, 'qty' => $rest, 'delivery_date' => $l->delivery_date?->toDateString(), 'delivery_time' => $l->delivery_time, 'memo' => $l->memo];
                    }
                }
            }
        }
        $nummers = $orders->keys()->merge($res->keys())->unique();
        $empIds = collect();
        $rijen = $nummers->map(function ($nr) use ($r, $orders, $res, $codes, $notes, $vandaag, $plRegels, $inHuur, $teLeveren, $pickinfoBekend, &$empIds) {
            $o = $orders->get($nr);
            $v = $res->get($nr);
            $isOfferte = $o === null && $v && ! (bool) $v->is_contract && ! (bool) $v->is_bijhuur;
            $soort = $o?->soort ?? ($v ? (str_starts_with(mb_strtolower((string) $v->type), 'verkoop') ? 'verkoop' : 'huur') : 'overig');
            $empId = $o?->employee_id ?: ($v && $v->created_by_code ? ($codes[$v->created_by_code] ?? null) : null);
            if ($empId) {
                $empIds->push($empId);
            }
            // Datums altijd als 'Y-m-d'-string: de Eloquent-cast maakt van de aggregaten een Carbon, en die
            // wordt in JSON een UTC-tijdstip (23:00 van de dag ervoor) → verkeerde dagkop en volgorde in het dashboard
            $dag = fn ($x) => $x instanceof \DateTimeInterface ? $x->format('Y-m-d') : ($x ? substr((string) $x, 0, 10) : null);
            $datum = $o?->delivery_date?->toDateString() ?? $dag($v?->delivery_date);
            // Bijhuur: de echte leverdatum is die van de nieuwe regels (gegroepeerde 8.7), niet de oude contractdatum
            $datumContract = $datum;
            $nieuweRegels = $teLeveren[$nr] ?? [];
            $datumOnbekend = false;
            if ($nieuweRegels) {
                $d = collect($nieuweRegels)->pluck('delivery_date')->filter()->sort()->first();
                if ($d) {
                    $datum = $d;
                }
            } elseif (! $r && isset($inHuur[$nr]) && $o && (float) $o->total > (float) $o->picked && $datum !== null && $datum < $vandaag) {
                // Alleen zonder 3.4-export: bijhuur op een lopend contract waarvan de platte 8.7 de oude contractdatum
                // houdt → vandaag tonen. Mét 3.4 bepalen de regeldatums dit en is zo'n order zonder 3.4-regels gewoon oud.
                $datum = $vandaag;
                $datumOnbekend = true;
            }
            $total = $o ? (float) $o->total : null;
            $picked = $o ? (float) $o->picked : null;
            // Alleen huur- en verkooporders: bij transfers en retouren is PickUpInfo niet van toepassing (Wim 13-11-2026)
            $nietOpScanner = $pickinfoBekend && $o && trim((string) $o->pick_up_info) === '' && in_array($soort, ['huur', 'verkoop'], true)
                && ($total <= 0 || $picked < $total) && ($datum === null || $datum >= $vandaag);
            // 8.7 kolom P (REF) leeg of 'volgt' / 'PO volgt' = klantreferentie/PO-nummer ontbreekt → factuur wordt niet betaald (Wim 13-11-2026)
            $refOntbreekt = $o && in_array($soort, ['huur', 'verkoop'], true) && ($datum === null || $datum >= $vandaag) && self::referentieOntbreekt($o->reference);
            // Uitgeleverd (Wim 13-11-2026): het contract staat in de WOW (artikelen On-Hire/Delivered), er zijn geen nieuwe
            // 3.4-regels meer te leveren, en de 8.7-order is volledig gepickt (of er is geen 8.7-order meer). De 8.7 blijft zo'n
            // order tonen; in de planning hoort hij niet meer.
            $geleverd = ! $isOfferte && isset($inHuur[$nr]) && $nieuweRegels === [] && ! $datumOnbekend
                && ($o === null || ($total !== null && $total > 0 && $picked >= $total));
            // Order met uitsluitend brandstof/arbeid/heffingen (bv. gasolie bijboeken): niets te picken → niet in de planning
            $alleenNietPickbaar = ! $isOfferte && $o === null && $v && (int) $v->regels > 0 && (int) $v->niet_pickbaar >= (int) $v->regels;
            if ($alleenNietPickbaar) {
                $geleverd = true;
            }
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
                'alleen_niet_pickbaar' => $alleenNietPickbaar,
                'pick_status' => $alleenNietPickbaar ? 'niet_pickbaar' : ($geleverd ? 'geleverd' : ($total === null ? 'onbekend' : ($total <= 0 ? 'geen_regels' : ($picked >= $total ? 'volledig' : ($picked > 0 ? 'deels' : 'niets'))))),
                'nog_te_picken' => $total !== null && $total > 0 ? max(0, $total - $picked) : null,
                // Bijhuur (Wim 13-11-2026): contract staat al in huur (WOW) én 3.4 bevat aanvullende regels die nog niet uitstaan
                'bijhuur' => isset($inHuur[$nr]) && ($nieuweRegels !== [] || $datumOnbekend),
                'delivery_date_contract' => $datumContract,
                'datum_onbekend' => $datumOnbekend,
                'nieuwe_regels' => array_values($nieuweRegels),
                'in_huur_regels' => isset($inHuur[$nr]) ? (int) $inHuur[$nr]->n : 0,
                'in_huur_sinds' => isset($inHuur[$nr]) ? $inHuur[$nr]->laatste : null,
                'contract_status' => $o?->contract_status ?? $v?->contract_status,
                'offerte_status' => $v?->offerte_status,
                'goods_value' => $v ? (float) $v->goods_value : null,
                'regels' => $v ? (int) $v->regels : (isset($plRegels[$nr]) ? (int) $plRegels[$nr]->n : null),
                'aantal' => $v ? (float) $v->aantal : (isset($plRegels[$nr]) ? (float) $plRegels[$nr]->aantal : null),
                'regels_bron' => $v ? 'reserveringen' : (isset($plRegels[$nr]) ? 'planning_regels' : null),
                // Verhuurdatum (start huur) ≠ leverdatum: uit 3.4 HIRE START DATE, anders de eerste huurdatum in WOW
                'hire_start_date' => $dag($v?->hire_start_date) ?? (isset($inHuur[$nr]) ? $dag($inHuur[$nr]->eerste) : null),
                'hire_end_date' => $dag($v?->hire_end_date),
                'delivery_name' => $v?->delivery_name, 'street' => $v?->street, 'city' => $v?->city,
                'ordered_by' => $v?->ordered_by,
                'reference' => $o?->reference ?? $v?->customer_reference,
                'memo' => $o?->del_memo,
                'pick_up_info' => $o?->pick_up_info,
                'niet_op_scanner' => $nietOpScanner,
                'geleverd' => $geleverd,
                'referentie_ontbreekt' => $refOntbreekt,
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
            // Uitgeleverde orders horen niet meer in de planning (wel op te vragen met geleverd=1 of alles=1)
            if (! isset($f['geleverd']) || $f['geleverd'] === '' || $f['geleverd'] === null) {
                $rijen = $rijen->where('geleverd', false);
            }
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
        if (! empty($f['bijhuur'])) {
            $rijen = $rijen->where('bijhuur', true);
        }
        if (! empty($f['niet_op_scanner'])) {
            $rijen = $rijen->where('niet_op_scanner', true);
        }
        if (! empty($f['referentie_ontbreekt'])) {
            $rijen = $rijen->where('referentie_ontbreekt', true);
        }
        if (isset($f['geleverd']) && $f['geleverd'] !== '' && $f['geleverd'] !== null) {
            $rijen = $rijen->where('geleverd', (bool) (int) $f['geleverd']);
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

    /**
     * Brandstofregel (Wim 13-11-2026): gasolie/diesel die op een contract wordt bijgeboekt (verkoopartikel "per liter",
     * bv. 44331 "Diesel fuel on return per litre" / "Gasolie bij retourname per liter"). Hoeft niet gepickt te worden en
     * telt dus niet als nieuwe regel/bijhuur. Aggregaten, compressors, brandstoftanks en -slangen met 'diesel' of
     * 'brandstof' in de naam zijn gewoon materieel en tellen wél.
     */
    public static function isBrandstof(?string $omschrijving, ?string $itemNumber = null, ?string $type = null): bool
    {
        if (in_array((string) $itemNumber, ['44331'], true)) {
            return true;
        }
        $d = mb_strtolower((string) $omschrijving);
        $brandstof = preg_match('/gasolie|diesel|fuel|brandstof|benzine|adblue|hvo/u', $d);
        $perLiter = preg_match('/\blit(er|re)s?\b|\bltr\b|retourname|on return|bijgevuld|bijvullen|getankt|tanken/u', $d);

        return (bool) ($brandstof && $perLiter);
    }

    /** Niet te picken door de expeditie: brandstof, arbeid/uren en heffingen/toeslagen (geen fysiek artikel). */
    public static function isNietPickbaar(?string $omschrijving, ?string $itemNumber = null, ?string $type = null): bool
    {
        if (self::isBrandstof($omschrijving, $itemNumber, $type)) {
            return true;
        }
        $d = mb_strtolower((string) $omschrijving);

        return (bool) preg_match('/\barbeid\b|monteur.*per uur|\bper uur\b|uurtarief|milieuheffing|\bheffing\b|\btoeslag\b|verzekering|waiver|administratiekosten/u', $d);
    }

    /** Leeg, alleen leestekens, of 'volgt' / 'PO volgt' / 'referentie volgt' / 'nvt' = er is nog geen klantreferentie of PO-nummer. */
    public static function referentieOntbreekt(?string $ref): bool
    {
        $r = mb_strtolower(trim((string) $ref));
        if ($r === '' || preg_match('/^[\s\-\?\.\_]*$/u', $r)) {
            return true;
        }

        return (bool) preg_match('/\bvolgt\b|^(n\.?v\.?t\.?|geen|onbekend|nog niet bekend|tbd|nnb)$/u', $r);
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

        // Markering per regel: staat dit artikel al in huur (WOW) of moet het nog geleverd worden?
        $wow = SapUpload::laatste('wow');
        $uitstaand = $wow ? HireLine::where('upload_id', $wow->id)->where('contract_number', $nr)->selectRaw('item_number, SUM(qty_out) AS q')->groupBy('item_number')->pluck('q', 'item_number') : collect();
        $regels = collect($regels)->map(function ($r) use ($uitstaand) {
            $r = is_array($r) ? $r : $r->toArray();
            $al = (float) ($uitstaand[$r['item_number']] ?? 0);
            $r['al_in_huur'] = $al;
            $r['brandstof'] = self::isBrandstof($r['item_description'] ?? null, $r['item_number'] ?? null, $r['type'] ?? null);
            $r['niet_pickbaar'] = self::isNietPickbaar($r['item_description'] ?? null, $r['item_number'] ?? null, $r['type'] ?? null);
            $r['te_leveren'] = $r['niet_pickbaar'] ? 0 : max(0, (float) $r['qty'] - $al);
            if (! empty($r['item_delivery_date'])) {
                $r['delivery_date'] = is_string($r['item_delivery_date']) ? substr($r['item_delivery_date'], 0, 10) : $r['item_delivery_date'];
            }

            return $r;
        })->values();

        return ['order' => $rij, 'regels' => $regels];
    }
}
