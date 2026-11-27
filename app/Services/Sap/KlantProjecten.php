<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use Illuminate\Database\Eloquent\Builder;

/**
 * Smartview-data voor een klantportaal (eerst Shell, shell.sorai.nl) — server-to-server via
 * /api/internal/klant-projecten (IP-guard). De klantapp heeft zelf al de huurlijst (eigen WoW-export
 * met nettotarief en besteller); CORE vult aan wat die export niet heeft:
 *   - ophalen: afgemelde regels (On-Hire - To Collect) met de GEPLANDE ophaaldatum/-tijd uit de WOW;
 *   - komt:    nog te leveren regels uit de 3.4 (leverdatum per regel), minus wat al in de WOW uitstaat.
 * Bewust GEEN tarieven, pickstand, verkoper of interne notities: dit gaat naar een klant.
 *
 * Klantherkenning: debiteurnummers (exact, ook contractors die voor de klant huren) OF een naamdeel.
 */
class KlantProjecten
{
    public function voorKlant(?string $naam, array $debiteuren): array
    {
        $naam = trim((string) $naam);
        $debiteuren = array_values(array_unique(array_filter(array_map(fn ($d) => trim((string) $d), $debiteuren), fn ($d) => $d !== '')));
        $wow = SapUpload::laatste('wow');
        $res = SapUpload::laatste('reserveringen');   // 3.4 (contract_items) wordt hier opgeslagen
        $data = app(HireData::class);

        $uit = [
            'beschikbaar' => (bool) $wow,
            // alleen het moment: uploadInfo() bevat ook de uploader en totalen van alle klanten
            'wow' => $wow ? ['moment' => $wow->created_at->toDateTimeString(), 'rapportdatum' => $wow->rapportdatum?->toDateString()] : null,
            'leveringen' => $res ? ['moment' => $res->created_at->toDateTimeString(), 'rapportdatum' => $res->rapportdatum?->toDateString()] : null,
            'ophalen' => [],
            'komt' => [],
        ];
        if ($naam === '' && $debiteuren === []) {
            return $uit;
        }
        $vandaag = now()->toDateString();
        $d = fn ($v) => $v ? (is_string($v) ? substr($v, 0, 10) : $v->format('Y-m-d')) : null;

        if ($wow) {
            $uit['ophalen'] = $this->filter(HireLine::where('upload_id', $wow->id), $naam, $debiteuren)
                ->where('status', 'like', '%To Collect%')
                ->orderByRaw('CASE WHEN coll_date IS NULL THEN 1 ELSE 0 END')->orderBy('coll_date')
                ->get(['contract_number', 'customer_number', 'customer_name', 'project_number', 'project_description', 'item_number', 'item_description',
                    'qty_out', 'est_return_date', 'coll_date', 'coll_time', 'delivery_name'])
                ->map(fn ($r) => [
                    'contract_number' => $r->contract_number, 'customer_number' => $r->customer_number, 'customer_name' => $r->customer_name,
                    'project_number' => $r->project_number, 'project_description' => $r->project_description,
                    'item_number' => $r->item_number, 'item_description' => $r->item_description, 'qty' => (float) $r->qty_out,
                    'afgemeld_per' => $d($r->est_return_date), 'coll_date' => $d($r->coll_date), 'coll_time' => $r->coll_time ?: null,
                    'te_laat' => $r->coll_date && $d($r->coll_date) < $vandaag,
                    'klant_brengt_terug' => mb_strtolower((string) $r->delivery_name) === 'afhalen',
                ])->values()->all();
        }

        if ($res) {
            $regels = $this->filter(ReservationLine::where('upload_id', $res->id), $naam, $debiteuren)
                ->where('is_contract', true)
                ->get();
            // Wat er per contract + artikel al uitstaat (bijhuur op een lopend contract): die aantallen zijn al geleverd
            $contracten = $regels->pluck('contract_number')->unique()->values();
            $uitstaand = $wow && $contracten->isNotEmpty()
                ? HireLine::where('upload_id', $wow->id)->whereIn('contract_number', $contracten)
                    ->selectRaw('contract_number, item_number, SUM(qty_out) AS q')->groupBy('contract_number', 'item_number')->get()
                    ->groupBy('contract_number')->map(fn ($g) => $g->pluck('q', 'item_number')->map(fn ($q) => (float) $q)->all())->all()
                : [];
            $inHuur = array_fill_keys(array_keys($uitstaand), true);
            $project = [];   // projectnummer per contract (WOW-regel, WOW-historie of 8.A)
            $komt = [];
            foreach ($regels as $l) {
                if (PlanningOverzicht::isNietPickbaar($l->item_description, $l->item_number, $l->type)) {
                    continue;   // brandstof, arbeid, heffingen: niets dat geleverd wordt
                }
                $lever = $d($l->item_delivery_date) ?? $d($l->delivery_date);
                if (! $lever || $lever < $vandaag) {
                    continue;   // verleden leverdatum zonder WOW-regel = op te schonen in InspHire, niet tonen aan de klant
                }
                $rest = (float) $l->qty;
                $al = $uitstaand[$l->contract_number][$l->item_number] ?? 0.0;
                if ($al > 0) {
                    $aftrek = min($al, $rest);
                    $rest -= $aftrek;
                    $uitstaand[$l->contract_number][$l->item_number] = $al - $aftrek;
                }
                if ($rest <= 0) {
                    continue;
                }
                $project[$l->contract_number] ??= $data->projectVoorContract((string) $l->contract_number, null, null);
                $komt[] = [
                    'contract_number' => $l->contract_number, 'customer_number' => $l->customer_number, 'customer_name' => $l->customer_name,
                    'project_number' => $project[$l->contract_number]['project_number'], 'project_description' => $project[$l->contract_number]['project_description'],
                    'item_number' => $l->item_number, 'item_description' => $l->item_description, 'qty' => $rest,
                    'delivery_date' => $lever, 'hire_end_date' => $d($l->hire_end_date),
                    'customer_reference' => $l->customer_reference, 'ordered_by' => $l->ordered_by,
                    'delivery_name' => $l->delivery_name, 'street' => $l->street, 'city' => $l->city,
                    'bijhuur' => isset($inHuur[$l->contract_number]),
                ];
            }
            usort($komt, fn ($a, $b) => [$a['delivery_date'], $a['contract_number']] <=> [$b['delivery_date'], $b['contract_number']]);
            $uit['komt'] = $komt;
        }

        return $uit;
    }

    private function filter(Builder $q, string $naam, array $debiteuren): Builder
    {
        return $q->where(function ($w) use ($naam, $debiteuren) {
            if ($debiteuren !== []) {
                $w->orWhereIn('customer_number', $debiteuren);
            }
            if ($naam !== '') {
                $w->orWhere('customer_name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $naam).'%');
            }
        });
    }
}
