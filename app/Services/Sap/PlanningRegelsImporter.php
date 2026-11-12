<?php

namespace App\Services\Sap;

use App\Models\PlanningLine;

/**
 * Gegroepeerde 8.7-export: groepregels "COLLECTION DATE/TIME: …" (A) en "CONTRACTS.CONTNO: …" (B),
 * itemregels in D–T onder de kop. Levert alleen artikelregels per order; de orderstatus/pickstand
 * komt uit de platte View (soort 'planning'). Kolom A/C (collection-tekst) wordt genegeerd.
 */
class PlanningRegelsImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $r->rapportdatum = now()->toDateString();
        $depot = (string) config('sap.depot', '');
        $c = fn (string $naam) => $k[mb_strtolower($naam)] ?? null;
        $kol = [
            'project' => $c('PROJECTNUMBER'), 'acc' => $c('ACCOUNT'), 'accnaam' => $c('ACCOUNT NAME'), 'ref' => $c('THEIRREF'),
            'cstatus' => $c('CONTRACT STATUS'), 'istatus' => $c('ITEM STATUS'), 'item' => $c('ITEM NUMBER'), 'oms' => $c('IITEM DESCRIPTION') ?? $c('ITEM DESCRIPTION'),
            'qty' => $c('QTY'), 'place' => $c('PLACE OF DELIVERY'), 'ddate' => $c('DELIVER DATA') ?? $c('DELIVER DATE'), 'dtime' => $c('DELIVER TIME'),
            'contract' => $c('CONTRACTS.CONTNO'), 'created' => $c('CREATED BY'), 'sales' => $c('SALES PERSON'), 'memo' => $c('MEMO'),
        ];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);
        $buffer = [];
        $contracten = [];
        foreach (array_slice($rijen, $koprij + 1) as $rij) {
            $contract = SapBestand::tekst($v($rij, 'contract'));
            $item = SapBestand::tekst($v($rij, 'item'));
            if (! $contract || ! preg_match('/^\d{6,}$/', $contract) || ! $item) {
                $r->overgeslagen++;
                continue;
            }
            if ($depot !== '' && ! str_starts_with($contract, $depot) && ! str_starts_with($contract, '272')) {
                $r->overgeslagen++;
                continue;
            }
            $buffer[] = [
                'upload_id' => $uploadId,
                'contract_number' => $contract,
                'project_number' => SapBestand::tekst($v($rij, 'project')),
                'customer_number' => SapBestand::tekst($v($rij, 'acc')),
                'customer_name' => SapBestand::tekst($v($rij, 'accnaam')),
                'their_ref' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255),
                'contract_status' => SapBestand::tekst($v($rij, 'cstatus')),
                'item_status' => SapBestand::tekst($v($rij, 'istatus')),
                'item_number' => $item,
                'item_description' => SapBestand::tekst($v($rij, 'oms')),
                'qty' => SapBestand::getal($v($rij, 'qty')) ?: 1.0,
                'place_of_delivery' => SapBestand::tekst($v($rij, 'place')),
                'delivery_date' => SapBestand::datum($v($rij, 'ddate'))?->toDateString(),
                'delivery_time' => SapBestand::tijd($v($rij, 'dtime')),
                'created_by_code' => ($x = SapBestand::tekst($v($rij, 'created'))) ? strtoupper($x) : null,
                'sales_person' => SapBestand::tekst($v($rij, 'sales')),
                'memo' => SapBestand::tekst($v($rij, 'memo')),
            ];
            $r->regels++;
            $contracten[$contract] = true;
            if (count($buffer) >= 500) {
                PlanningLine::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            PlanningLine::insert($buffer);
        }
        $r->contracten = count($contracten);
        $r->samenvatting = ['orders_met_regels' => count($contracten)];

        return $r;
    }
}
