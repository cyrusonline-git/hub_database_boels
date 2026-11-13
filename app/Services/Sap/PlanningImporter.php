<?php

namespace App\Services\Sap;

use App\Models\PlanningOrder;

/**
 * 8.7 INDUSTRIAL HOP, SOP and DT View — platte lijst, één rij per order.
 * Kolommen: PickUpDate, PickUpInfo (leeg = niet op de scanner van de expeditie), Account, Type, ContractStatus,
 * Items ("gepickt/totaal"), Location, DelMemo, DelMethod (negeren), Depot,
 * HiringDepot, Modification, ModMemo, Number, contno, REF, SalesPerson, DelDate.
 */
class PlanningImporter
{
    public static function soort(?string $type): string
    {
        $t = mb_strtolower(trim((string) $type));

        return match (true) {
            str_contains($t, 'transfer in') => 'transfer_in',
            str_contains($t, 'transfer out') => 'transfer_uit',
            str_starts_with($t, 'hire') => 'huur',
            str_starts_with($t, 'sales') => 'verkoop',
            str_starts_with($t, 'ohr'), str_contains($t, 'off hire') => 'retour',
            default => 'overig',
        };
    }

    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $r->rapportdatum = now()->toDateString();
        $c = fn (string $naam) => $k[mb_strtolower($naam)] ?? null;
        $kol = [
            'account' => $c('Account'), 'type' => $c('Type'), 'status' => $c('ContractStatus'), 'items' => $c('Items'), 'loc' => $c('Location'),
            'memo' => $c('DelMemo'), 'depot' => $c('Depot'), 'hdepot' => $c('HiringDepot'), 'number' => $c('Number') ?? $c('contno'),
            'ref' => $c('REF'), 'sales' => $c('SalesPerson'), 'deldate' => $c('DelDate') ?? $c('PickUpDate'), 'pickinfo' => $c('PickUpInfo'),
        ];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);
        $codes = app(SapUserCodes::class);
        $buffer = [];
        $perSoort = [];
        $oud = 0;
        $zonderPickinfo = 0;
        $vandaag = now()->toDateString();
        $gezien = [];
        foreach (array_slice($rijen, $koprij + 1) as $rij) {
            $nr = SapBestand::tekst($v($rij, 'number'));
            if (! $nr || isset($gezien[$nr])) {
                $r->overgeslagen++;
                continue;
            }
            $gezien[$nr] = true;
            $items = (string) SapBestand::tekst($v($rij, 'items'));
            $picked = $total = 0.0;
            if (preg_match('/([\d.,]+)\s*\/\s*([\d.,]+)/', $items, $m)) {
                $picked = (float) str_replace(',', '.', $m[1]);
                $total = (float) str_replace(',', '.', $m[2]);
            }
            $sales = SapBestand::tekst($v($rij, 'sales'));
            $datum = SapBestand::datum($v($rij, 'deldate'));
            $soort = self::soort(SapBestand::tekst($v($rij, 'type')));
            $pickinfo = SapBestand::tekst($v($rij, 'pickinfo'));
            if ($pickinfo === null && $soort !== 'retour' && ($total <= 0 || $picked < $total) && (! $datum || $datum->toDateString() >= $vandaag)) {
                $zonderPickinfo++;
            }
            $buffer[] = [
                'upload_id' => $uploadId,
                'order_number' => $nr,
                'type_raw' => SapBestand::tekst($v($rij, 'type')),
                'soort' => $soort,
                'customer_name' => SapBestand::tekst($v($rij, 'account')),
                'contract_status' => SapBestand::tekst($v($rij, 'status')),
                'picked' => $picked,
                'total' => $total,
                'location' => SapBestand::tekst($v($rij, 'loc')),
                'del_memo' => SapBestand::tekst($v($rij, 'memo')),
                'pick_up_info' => $pickinfo === null ? null : mb_substr($pickinfo, 0, 255),
                'depot' => SapBestand::tekst($v($rij, 'depot')),
                'hiring_depot' => SapBestand::tekst($v($rij, 'hdepot')),
                'reference' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255),
                'sales_person' => $sales,
                'employee_id' => $sales ? $codes->employeeId($sales) : null,
                'delivery_date' => $datum?->toDateString(),
            ];
            $r->regels++;
            $perSoort[$soort] = ($perSoort[$soort] ?? 0) + 1;
            if ($datum && $datum->toDateString() < $vandaag) {
                $oud++;
            }
        }
        if ($buffer) {
            PlanningOrder::insert($buffer);
        }
        $r->contracten = count($gezien);
        // pickinfo_bekend: snapshots van vóór deze kolom tonen geen 'niet op scanner'-markering
        $r->samenvatting = ['per_soort' => $perSoort, 'op_te_schonen' => $oud, 'niet_op_scanner' => $zonderPickinfo, 'pickinfo_bekend' => true];

        return $r;
    }
}
