<?php

namespace App\Services\Sap;

use App\Models\Customer;
use App\Models\HireLine;
use App\Models\Machine;
use Illuminate\Support\Facades\DB;

/**
 * WOW — "What's Out Where": alles wat in huur staat (Crystal Reports .xls,
 * kop op rij 2, één regel per artikel per contract). Alleen contracten van
 * config('sap.depot') (eerste 3 cijfers van het contractnummer). Kolom lm negeren.
 * EstRtDate gelijk aan de rapportdatum (= dominante waarde) betekent: geen
 * einddatum bekend → NULL.
 */
class WowImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $depot = (string) config('sap.depot', '');
        $c = fn (string $naam) => $k[mb_strtolower($naam)] ?? null;
        $kol = [
            'exdp' => $c('ExDp'), 'cid' => $c('CID'), 'item' => $c('Item Number'), 'oms' => $c('Item Description'), 'qty' => $c('Qty Out'),
            'hire' => $c('Hire Date'), 'est' => $c('EstRtDate'), 'coll' => $c('Coll. Date'), 'time' => $c('Time'), 'printed' => $c('Coll. Document Printed'),
            'contract' => $c('Contract Number'), 'cur' => $c('Currency'), 'weekrate' => $c('Week Rate'), 'acc' => $c('Account'), 'accnaam' => $c('Account Name'),
            'ref' => $c('Your Reference / Order Number'), 'deliv' => $c('DeliveryDate'), 'dnaam' => $c('Delivery Address Name'),
            'a1' => $c('Delivery Address#1'), 'a2' => $c('Delivery Address#2'), 'a3' => $c('Delivery Address#3'), 'a4' => $c('Delivery Address#4'), 'a5' => $c('Delivery Address#5'),
            'pc' => $c('Delivery Address Postal Code'), 'status' => $c('Status'), 'proj' => $c('Project Number'), 'projoms' => $c('Project Description'),
            'whr' => $c('Weekly Hire Rate'), 'disc' => $c('Discount'), 'waiver' => $c('Damage / Fire Theft waiver'), 'notes' => $c('Notes'),
        ];
        foreach (['item', 'contract', 'status', 'whr'] as $verplicht) {
            if ($kol[$verplicht] === null) {
                throw new \RuntimeException("Kolom voor '$verplicht' ontbreekt in het WOW-bestand.");
            }
        }
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);

        // Eerste doorloop: dominante EstRtDate = rapportdatum
        $data = array_slice($rijen, $koprij + 1);
        $ests = [];
        foreach ($data as $rij) {
            $ests[] = SapBestand::datum($v($rij, 'est'));
        }
        $r->rapportdatum = SapBestand::dominanteDatum($ests) ?? now()->toDateString();

        $klanten = Customer::query()->pluck('id', 'customer_number')->all();
        $machines = [];
        $buffer = [];
        $contracten = [];
        $perKlant = [];
        $perProject = [];
        $toCollect = 0;
        $weekTotaal = 0.0;

        foreach ($data as $i => $rij) {
            $contract = SapBestand::tekst($v($rij, 'contract'));
            $item = SapBestand::tekst($v($rij, 'item'));
            if (! $contract || ! $item) {
                $r->overgeslagen++;
                continue;
            }
            if ($depot !== '' && ! str_starts_with($contract, $depot)) {
                $r->overgeslagen++;
                continue;
            }
            $qty = SapBestand::getal($v($rij, 'qty')) ?: 1.0;
            $whr = SapBestand::getal($v($rij, 'whr'));
            $est = $ests[$i];
            $coll = SapBestand::datum($v($rij, 'coll'));
            $status = SapBestand::tekst($v($rij, 'status')) ?? '';
            $klantnr = SapBestand::tekst($v($rij, 'acc'));
            $printed = $v($rij, 'printed');
            $adres = implode(', ', array_filter(array_map(fn ($n) => SapBestand::tekst($v($rij, $n)), ['a1', 'a2', 'a3', 'a4', 'a5'])));
            if (! array_key_exists($item, $machines)) {
                $machines[$item] = strlen($item) >= 7 ? Machine::where('machine_number', $item)->value('id') : null;
            }
            $buffer[] = [
                'upload_id' => $uploadId,
                'depot' => substr($contract, 0, 3),
                'contract_number' => $contract,
                'customer_number' => $klantnr,
                'customer_name' => SapBestand::tekst($v($rij, 'accnaam')),
                'customer_id' => $klantnr ? ($klanten[$klantnr] ?? null) : null,
                'project_number' => SapBestand::tekst($v($rij, 'proj')),
                'project_description' => SapBestand::tekst($v($rij, 'projoms')),
                'item_number' => $item,
                'machine_id' => $machines[$item],
                'item_description' => SapBestand::tekst($v($rij, 'oms')),
                'qty_out' => $qty,
                'hire_date' => SapBestand::datum($v($rij, 'hire'))?->toDateString(),
                'delivery_date' => SapBestand::datum($v($rij, 'deliv'))?->toDateString(),
                'est_return_date' => ($est && $est->toDateString() !== $r->rapportdatum) ? $est->toDateString() : null,
                'coll_date' => ($coll && $coll->toDateString() !== $r->rapportdatum) ? $coll->toDateString() : null,
                'coll_time' => SapBestand::tijd($v($rij, 'time')),
                'coll_doc_printed' => $printed !== null && trim((string) $printed) !== '' && trim((string) $printed) !== '0',
                'status' => $status,
                'week_rate' => SapBestand::getal($v($rij, 'weekrate')),
                'discount' => SapBestand::getal($v($rij, 'disc')),
                'weekly_hire_rate' => $whr,
                'waiver' => SapBestand::getal($v($rij, 'waiver')),
                'currency' => SapBestand::tekst($v($rij, 'cur')),
                'your_reference' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255),
                'delivery_name' => SapBestand::tekst($v($rij, 'dnaam')),
                'delivery_address' => $adres !== '' ? mb_substr($adres, 0, 255) : null,
                'delivery_postal_code' => SapBestand::tekst($v($rij, 'pc')),
                'notes' => SapBestand::tekst($v($rij, 'notes')),
                'exdp' => SapBestand::tekst($v($rij, 'exdp')),
                'cid' => SapBestand::tekst($v($rij, 'cid')),
            ];
            $r->regels++;
            $contracten[$contract] = true;
            $weekTotaal += $whr * $qty;
            $kn = SapBestand::tekst($v($rij, 'accnaam')) ?? ($klantnr ?? '?');
            $perKlant[$kn] = ($perKlant[$kn] ?? 0) + $whr * $qty;
            if ($p = SapBestand::tekst($v($rij, 'proj'))) {
                $perProject[$p] = ($perProject[$p] ?? 0) + $whr * $qty;
            }
            if (str_contains($status, 'To Collect')) {
                $toCollect++;
            }
            if (count($buffer) >= 500) {
                HireLine::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            HireLine::insert($buffer);
        }
        arsort($perKlant);
        $r->contracten = count($contracten);
        $r->samenvatting = [
            'weekhuur' => round($weekTotaal, 2),
            'klanten' => count($perKlant),
            'projecten' => count($perProject),
            'to_collect_regels' => $toCollect,
            'top_klanten' => array_map(fn ($b) => round($b, 2), array_slice($perKlant, 0, 10, true)),
        ];

        return $r;
    }
}
