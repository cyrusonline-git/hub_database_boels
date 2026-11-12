<?php

namespace App\Services\Sap;

use App\Models\ReservationLine;

/**
 * 2.6 Reservations with items (today and future) — gegroepeerd rapport
 * (HIRE START DATE: / ACCOUNT NAME: / CONTRACT NUMBER: groepregels; itemregels
 * één kolom verschoven, klantnummer in D). Kolom H CONTRACT STATUS bepaalt
 * contract (Ingegeven) of offerte (Offerte); kolom G is de offertestatus.
 */
class ReserveringenImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $r->rapportdatum = now()->toDateString();
        $depot = (string) config('sap.depot', '');
        $contractKol = $k['contract number'];
        $data = array_slice($rijen, $koprij + 1);
        $shift = 0;
        foreach ($data as $rij) {
            if (preg_match('/^\d{8,}$/', (string) SapBestand::tekst($rij[$contractKol] ?? null))) {
                $shift = 0;
                break;
            }
            if (preg_match('/^\d{8,}$/', (string) SapBestand::tekst($rij[$contractKol - 1] ?? null))) {
                $shift = -1;
                break;
            }
        }
        $c = fn (string $naam) => isset($k[mb_strtolower($naam)]) ? $k[mb_strtolower($naam)] + $shift : null;
        $kol = [
            'acc' => isset($k['account number']) ? $k['account number'] + 3 + $shift : null, // kop in A, waarde in D
            'accnaam' => $c('ACCOUNT NAME'), 'contract' => $c('CONTRACT NUMBER'), 'ostatus' => $c('OFFERTE STATUS'), 'cstatus' => $c('CONTRACT STATUS'),
            'item' => $c('ITEM NUMBER'), 'oms' => $c('ITEM DESCRIPTION'), 'qty' => $c('QTY'), 'deliv' => $c('DELIVERY DATE'), 'hstart' => $c('HIRE START DATE'),
            'hend' => $c('HIRE END DATE'), 'hendtime' => $c('HIRE END TIME'), 'transport' => $c('TRANSPORT'), 'unconf' => $c('UNCONFIRMED COLLECTION'),
            'type' => $c('TYPE'), 'dnaam' => $c('DELIVERY NAME'), 'street' => $c('STREET'), 'city' => $c('CITY'), 'ordered' => $c('ORDERED BY'),
            'ref' => $c('CUSTOMER REFERENCE'), 'value' => $c('TOTAL GOODS VALUE'), 'created' => $c('CREATED BY'),
        ];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);
        $codes = app(SapUserCodes::class);
        $buffer = [];
        $contracten = [];
        $waarde = ['contract' => 0.0, 'offerte' => 0.0];
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
            $isContract = mb_strtolower((string) $cstatus) !== 'offerte';
            $code = SapBestand::tekst($v($rij, 'created'));
            $value = SapBestand::getal($v($rij, 'value'));
            $buffer[] = [
                'upload_id' => $uploadId,
                'contract_number' => $contract,
                'customer_number' => SapBestand::tekst($v($rij, 'acc')),
                'customer_name' => SapBestand::tekst($v($rij, 'accnaam')),
                'is_contract' => $isContract,
                'offerte_status' => SapBestand::tekst($v($rij, 'ostatus')),
                'contract_status' => $cstatus,
                'item_number' => SapBestand::tekst($v($rij, 'item')),
                'item_description' => SapBestand::tekst($v($rij, 'oms')),
                'qty' => SapBestand::getal($v($rij, 'qty')) ?: 1.0,
                'delivery_date' => SapBestand::datum($v($rij, 'deliv'))?->toDateString(),
                'hire_start_date' => SapBestand::datum($v($rij, 'hstart'))?->toDateString(),
                'hire_end_date' => SapBestand::datum($v($rij, 'hend'))?->toDateString(),
                'hire_end_time' => SapBestand::tijd($v($rij, 'hendtime')),
                'transport' => SapBestand::ja($v($rij, 'transport')),
                'unconfirmed_collection' => SapBestand::ja($v($rij, 'unconf')),
                'type' => SapBestand::tekst($v($rij, 'type')),
                'delivery_name' => SapBestand::tekst($v($rij, 'dnaam')),
                'street' => SapBestand::tekst($v($rij, 'street')),
                'city' => SapBestand::tekst($v($rij, 'city')),
                'ordered_by' => SapBestand::tekst($v($rij, 'ordered')),
                'customer_reference' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255),
                'goods_value' => $value,
                'created_by_code' => $code ? strtoupper($code) : null,
            ];
            $r->regels++;
            if (! isset($contracten[$contract])) {
                $contracten[$contract] = true;
                $waarde[$isContract ? 'contract' : 'offerte'] += $value;
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
        $r->samenvatting = ['waarde_contracten' => round($waarde['contract'], 2), 'waarde_offertes' => round($waarde['offerte'], 2)];

        return $r;
    }
}
