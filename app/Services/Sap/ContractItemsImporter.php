<?php

namespace App\Services\Sap;

use App\Models\ReservationLine;

/**
 * 3.4 "Contract Items due for delivery today and the future" — gegroepeerd rapport
 * (groepregels ITEM DELIVERY DATE: / CONTRACT NUMBER:), per regel de echte leverdatum
 * (kolom C). Bevat toekomstige contracten (Ingegeven), offertes (Offerte) én nieuwe regels
 * op lopende contracten (contractstatus Inhuur = bijhuur). Vervangt 2.6; schrijft naar
 * reservation_lines (upload-soort 'reserveringen').
 */
class ContractItemsImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $r->rapportdatum = now()->toDateString();
        $depot = (string) config('sap.depot', '');
        $contractKol = $k['contract number'];
        $data = array_slice($rijen, $koprij + 1);
        // Datarijen staan op de kopkolommen (D..W); de regeldatum staat in kolom C (kop A + 2)
        $shift = 0;
        foreach ($data as $rij) {
            if (preg_match('/^\d{8,}$/', (string) SapBestand::tekst($rij[$contractKol] ?? null))) {
                break;
            }
            if (preg_match('/^\d{8,}$/', (string) SapBestand::tekst($rij[$contractKol - 1] ?? null))) {
                $shift = -1;
                break;
            }
        }
        $c = fn (string $naam) => isset($k[mb_strtolower($naam)]) ? $k[mb_strtolower($naam)] + $shift : null;
        $kol = [
            'itemdate' => $k['item delivery date'] + 2 + $shift,
            'acc' => $c('ACCOUNT NUMBER'), 'accnaam' => $c('ACCOUNT NAME'), 'contract' => $c('CONTRACT NUMBER'), 'cstatus' => $c('CONTRACT STATUS'), 'istatus' => $c('ITEM STATUS'),
            'item' => $c('ITEM NUMBER'), 'oms' => $c('ITEM DESCRIPTION'), 'qty' => $c('QUANTITY'), 'deliv' => $c('DELIVERY DATE'), 'hstart' => $c('HIRE START DATE'),
            'coll' => $c('COLLECTION DATE'), 'hend' => $c('HIRE END DATE'), 'type' => $c('TYPE'), 'dnaam' => $c('DELIVERY NAME'), 'street' => $c('STREET'), 'city' => $c('CITY'),
            'ordered' => $c('ORDERED BY'), 'ref' => $c('CUSTOMER REFERENCE'), 'value' => $c('TOTAL GOODS VALUE'), 'created' => $c('Contracts.CREATEDBY') ?? $c('CREATED BY'),
        ];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);
        $codes = app(SapUserCodes::class);
        $buffer = [];
        $contracten = [];
        $tel = ['contract' => 0, 'offerte' => 0, 'bijhuur' => 0];
        foreach ($data as $rij) {
            $contract = SapBestand::tekst($v($rij, 'contract'));
            if (! $contract || ! preg_match('/^\d{6,}$/', $contract)) {
                $r->overgeslagen++;
                continue;
            }
            if ($depot !== '' && ! str_starts_with($contract, $depot)) {
                $r->overgeslagen++;
                continue;
            }
            $cstatus = SapBestand::tekst($v($rij, 'cstatus'));
            $soort = match (mb_strtolower((string) $cstatus)) { 'offerte' => 'offerte', 'inhuur' => 'bijhuur', default => 'contract' };
            $code = SapBestand::tekst($v($rij, 'created'));
            $buffer[] = [
                'upload_id' => $uploadId,
                'contract_number' => $contract,
                'customer_number' => SapBestand::tekst($v($rij, 'acc')),
                'customer_name' => SapBestand::tekst($v($rij, 'accnaam')),
                'is_contract' => $soort !== 'offerte',
                'is_bijhuur' => $soort === 'bijhuur',
                'offerte_status' => SapBestand::tekst($v($rij, 'istatus')),   // Offerte / Winst / Uitstaande (zoals kolom G van 2.6)
                'contract_status' => $cstatus,
                'item_number' => SapBestand::tekst($v($rij, 'item')),
                'item_description' => SapBestand::tekst($v($rij, 'oms')),
                'qty' => SapBestand::getal($v($rij, 'qty')) ?: 1.0,
                'delivery_date' => SapBestand::datum($v($rij, 'deliv'))?->toDateString(),
                'item_delivery_date' => SapBestand::datum($v($rij, 'itemdate'))?->toDateString(),
                'hire_start_date' => SapBestand::datum($v($rij, 'hstart'))?->toDateString(),
                'hire_end_date' => SapBestand::datum($v($rij, 'hend'))?->toDateString(),
                'hire_end_time' => null,
                'transport' => false,
                'unconfirmed_collection' => false,
                'type' => SapBestand::tekst($v($rij, 'type')),
                'delivery_name' => SapBestand::tekst($v($rij, 'dnaam')),
                'street' => SapBestand::tekst($v($rij, 'street')),
                'city' => SapBestand::tekst($v($rij, 'city')),
                'ordered_by' => SapBestand::tekst($v($rij, 'ordered')),
                'customer_reference' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255),
                'goods_value' => SapBestand::getal($v($rij, 'value')),
                'created_by_code' => $code ? strtoupper($code) : null,
            ];
            $r->regels++;
            if (! isset($contracten[$contract])) {
                $contracten[$contract] = true;
                $tel[$soort]++;
                if ($code) {
                    $codes->employeeId($code);
                }
            }
            if (count($buffer) >= 500) {
                ReservationLine::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            ReservationLine::insert($buffer);
        }
        $r->contracten = count($contracten);
        $r->samenvatting = ['contracten' => $tel['contract'], 'offertes' => $tel['offerte'], 'bijhuur_contracten' => $tel['bijhuur']];

        return $r;
    }
}
