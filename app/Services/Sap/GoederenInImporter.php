<?php

namespace App\Services\Sap;

use App\Models\GoodsInLine;

/**
 * 0.05 "Goods in to be off hired" — gegroepeerd rapport (groepregels "ACCOUNT NAME:" in A, "CONTRACT NUMBER:" in B).
 * Datarijen: klantnummer in kolom C (kop ACCOUNT NUMBER staat in A), verder alles onder de eigen kop.
 * Alleen contracten van config('sap.depot') (leeg = alle depots).
 */
class GoederenInImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $r->rapportdatum = now()->toDateString();
        $depot = (string) config('sap.depot', '');
        $data = array_slice($rijen, $koprij + 1);
        // Verschuiving bepalen: staat het contractnummer onder de kop of één kolom ervoor?
        $shift = 0;
        foreach (array_slice($data, 0, 50) as $rij) {
            if (preg_match('/^\d{8,}$/', (string) SapBestand::tekst($rij[$k['contract number']] ?? null))) {
                break;
            }
            if (preg_match('/^\d{8,}$/', (string) SapBestand::tekst($rij[$k['contract number'] - 1] ?? null))) {
                $shift = -1;
                break;
            }
        }
        $c = fn (string $naam) => isset($k[mb_strtolower($naam)]) ? $k[mb_strtolower($naam)] + $shift : null;
        $kol = [
            'acc' => isset($k['account number']) ? $k['account number'] + 2 + $shift : null,
            'naam' => $c('ACCOUNT NAME'), 'contract' => $c('CONTRACT NUMBER'), 'item' => $c('ITEM NUMBER'), 'oms' => $c('ITEM DESCRIPTION'),
            'qty' => $c('QUANTITY'), 'deliv' => $c('DELIVERY DATE'), 'hstart' => $c('HIRE START DATE'), 'hend' => $c('HIRE END DATE'),
            'coll' => $c('COLLECTION DATE'), 'gin' => $c('GOODS IN DATE ITEM'), 'type' => $c('ITEM TYPE'), 'dnaam' => $c('DELIVERY NAME'),
            'street' => $c('STREET'), 'city' => $c('CITY'), 'ordered' => $c('ORDERED BY'), 'ref' => $c('CUSTOMER REFERENCE'), 'created' => $c('CREATED BY'),
        ];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);
        $codes = app(SapUserCodes::class);
        $buffer = [];
        $contracten = [];
        $klanten = [];
        foreach ($data as $rij) {
            $contract = SapBestand::tekst($v($rij, 'contract'));
            $item = SapBestand::tekst($v($rij, 'item'));
            if (! $contract || ! $item || ! preg_match('/^\d{6,}$/', $contract)) {
                $r->overgeslagen++;
                continue;
            }
            if ($depot !== '' && ! str_starts_with($contract, $depot)) {
                $r->overgeslagen++;
                continue;
            }
            $code = SapBestand::tekst($v($rij, 'created'));
            $buffer[] = [
                'upload_id' => $uploadId,
                'contract_number' => $contract,
                'customer_number' => SapBestand::tekst($v($rij, 'acc')),
                'customer_name' => SapBestand::tekst($v($rij, 'naam')),
                'item_number' => mb_substr(strtoupper(preg_replace('/\s+/', '', $item)), 0, 30),
                'item_description' => mb_substr((string) SapBestand::tekst($v($rij, 'oms')), 0, 255) ?: null,
                'qty' => SapBestand::getal($v($rij, 'qty')) ?: 1.0,
                'delivery_date' => SapBestand::datum($v($rij, 'deliv'))?->toDateString(),
                'hire_start_date' => SapBestand::datum($v($rij, 'hstart'))?->toDateString(),
                'hire_end_date' => SapBestand::datum($v($rij, 'hend'))?->toDateString(),
                'collection_date' => SapBestand::datum($v($rij, 'coll'))?->toDateString(),
                'goods_in_date' => SapBestand::datum($v($rij, 'gin'))?->toDateString(),
                'item_type' => SapBestand::tekst($v($rij, 'type')),
                'delivery_name' => mb_substr((string) SapBestand::tekst($v($rij, 'dnaam')), 0, 255) ?: null,
                'street' => SapBestand::tekst($v($rij, 'street')),
                'city' => SapBestand::tekst($v($rij, 'city')),
                'ordered_by' => SapBestand::tekst($v($rij, 'ordered')),
                'customer_reference' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255) ?: null,
                'created_by_code' => $code ? strtoupper($code) : null,
            ];
            $r->regels++;
            $klanten[(string) SapBestand::tekst($v($rij, 'naam'))] = true;
            if (! isset($contracten[$contract])) {
                $contracten[$contract] = true;
                if ($code) {
                    $codes->employeeId($code);
                }
            }
            if (count($buffer) >= 500) {
                GoodsInLine::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            GoodsInLine::insert($buffer);
        }
        $r->contracten = count($contracten);
        $r->samenvatting = ['machines' => $r->regels, 'contracten' => count($contracten), 'klanten' => count($klanten)];

        return $r;
    }
}
