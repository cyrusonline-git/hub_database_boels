<?php

namespace App\Services\Sap;

use App\Models\InvoiceRunLine;
use App\Models\InvoiceRunNote;

/**
 * 0.07 — Contracts and items not part of invoice run. Gegroepeerd rapport:
 * groepregels "ACCOUNT NAME: …" / "CONTRACT NUMBER: …", itemregels één kolom
 * verschoven t.o.v. de kop. We lezen daarom op basis van de kop-index + 1 voor
 * alles vóór CONTRACT NUMBER en controleren met de waarde in de contractkolom.
 * DATE LAST INVOICE 30-12-1899 = nog nooit gefactureerd (NULL).
 */
class FactuurrunImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $depot = (string) config('sap.depot', '');
        $contractKol = $k['contract number'];
        $data = array_slice($rijen, $koprij + 1);
        $codes = app(SapUserCodes::class);

        // Kolomverschuiving bepalen: in de datarijen staat het contractnummer in $contractKol of $contractKol-1?
        $shift = 0;
        foreach ($data as $rij) {
            $hier = SapBestand::tekst($rij[$contractKol] ?? null);
            $links = SapBestand::tekst($rij[$contractKol - 1] ?? null);
            if ($hier && preg_match('/^\d{8,}$/', $hier)) {
                $shift = 0;
                break;
            }
            if ($links && preg_match('/^\d{8,}$/', $links)) {
                $shift = -1;
                break;
            }
        }
        $c = fn (string $naam) => isset($k[mb_strtolower($naam)]) ? $k[mb_strtolower($naam)] + $shift : null;
        $kol = [
            'acc' => isset($k['account number']) ? $k['account number'] + 2 + $shift : null, // kop in A, waarde in C
            'accnaam' => $c('ACCOUNT NAME'), 'contract' => $c('CONTRACT NUMBER'), 'item' => $c('ITEM NUMBER'), 'oms' => $c('ITEM DESCRIPTION'),
            'qty' => $c('QUANTITY'), 'istatus' => $c('ITEM STATUS'), 'deliv' => $c('DELIVERY DATE'), 'hstart' => $c('HIRE STARTDATE'),
            'hend' => $c('HIRE END DATE'), 'istart' => $c('ITEM HIRE START DATE'), 'coll' => $c('CONTRACTS.COLLDATE'), 'type' => $c('TYPE'),
            'lastinv' => $c('DATE LAST INVOICE'), 'dnaam' => $c('DELIVERY NAME'), 'street' => $c('STREET'), 'city' => $c('CITY'),
            'ordered' => $c('ORDERED BY'), 'ref' => $c('CUSTOMER REFERENCE'), 'created' => $c('CREATED BY'),
        ];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : ($rij[$kol[$naam]] ?? null);

        $ends = [];
        foreach ($data as $rij) {
            $ends[] = SapBestand::datum($v($rij, 'hend'));
        }
        $dominant = SapBestand::dominanteDatum($ends);
        $r->rapportdatum = $dominant ?? now()->toDateString();

        $buffer = [];
        $contracten = [];
        $nooit = [];
        $perCode = [];
        foreach ($data as $i => $rij) {
            $contract = SapBestand::tekst($v($rij, 'contract'));
            if (! $contract || ! preg_match('/^\d{6,}$/', $contract)) {
                $r->overgeslagen++;   // groepregel of lege regel
                continue;
            }
            if ($depot !== '' && ! str_starts_with($contract, $depot)) {
                $r->overgeslagen++;
                continue;
            }
            $hend = $ends[$i];
            $coll = SapBestand::datum($v($rij, 'coll'));
            $code = SapBestand::tekst($v($rij, 'created'));
            $last = SapBestand::datum($v($rij, 'lastinv'));
            $buffer[] = [
                'upload_id' => $uploadId,
                'contract_number' => $contract,
                'customer_number' => SapBestand::tekst($v($rij, 'acc')),
                'customer_name' => SapBestand::tekst($v($rij, 'accnaam')),
                'item_number' => SapBestand::tekst($v($rij, 'item')),
                'item_description' => SapBestand::tekst($v($rij, 'oms')),
                'quantity' => SapBestand::getal($v($rij, 'qty')) ?: 1.0,
                'item_status' => SapBestand::tekst($v($rij, 'istatus')),
                'delivery_date' => SapBestand::datum($v($rij, 'deliv'))?->toDateString(),
                'hire_start_date' => SapBestand::datum($v($rij, 'hstart'))?->toDateString(),
                'hire_end_date' => ($hend && $hend->toDateString() !== $dominant) ? $hend->toDateString() : null,
                'item_hire_start_date' => SapBestand::datum($v($rij, 'istart'))?->toDateString(),
                'coll_date' => ($coll && $coll->toDateString() !== $dominant) ? $coll->toDateString() : null,
                'type' => SapBestand::tekst($v($rij, 'type')),
                'last_invoice_date' => $last?->toDateString(),
                'delivery_name' => SapBestand::tekst($v($rij, 'dnaam')),
                'street' => SapBestand::tekst($v($rij, 'street')),
                'city' => SapBestand::tekst($v($rij, 'city')),
                'ordered_by' => SapBestand::tekst($v($rij, 'ordered')),
                'customer_reference' => mb_substr((string) SapBestand::tekst($v($rij, 'ref')), 0, 255),
                'created_by_code' => $code ? strtoupper($code) : null,
            ];
            $r->regels++;
            if (! isset($contracten[$contract])) {
                $contracten[$contract] = ['code' => $code ? strtoupper($code) : null, 'laatste' => null];
                $perCode[$code ? strtoupper($code) : '?'] = ($perCode[$code ? strtoupper($code) : '?'] ?? 0) + 1;
            }
            if ($last && (! $contracten[$contract]['laatste'] || $last->gt($contracten[$contract]['laatste']))) {
                $contracten[$contract]['laatste'] = $last;
            }
            if (count($buffer) >= 500) {
                InvoiceRunLine::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            InvoiceRunLine::insert($buffer);
        }
        foreach ($contracten as $nr => $info) {
            if (! $info['laatste']) {
                $nooit[] = $nr;
            }
            if ($info['code']) {
                $codes->employeeId($info['code']);
            }
        }

        // Notities bijhouden: eerst/laatst gezien, verdwenen markeren, teruggekeerd heropenen
        $vandaag = now()->toDateString();
        foreach (array_keys($contracten) as $nr) {
            $n = InvoiceRunNote::firstOrNew(['contract_number' => $nr]);
            if (! $n->exists) {
                $n->eerst_gezien = $vandaag;
                $n->status = 'te_controleren';
            }
            if ($n->verdwenen_op) {
                $n->verdwenen_op = null;   // komt terug → weer open, reden blijft staan
            }
            $n->laatst_gezien = $vandaag;
            $n->save();
        }
        InvoiceRunNote::whereNull('verdwenen_op')->whereNotIn('contract_number', array_keys($contracten))->update(['verdwenen_op' => $vandaag]);

        $r->contracten = count($contracten);
        $r->samenvatting = [
            'nooit_gefactureerd' => count($nooit),
            'per_code' => $perCode,
            'zonder_reden' => InvoiceRunNote::whereNull('verdwenen_op')->where(fn ($q) => $q->whereNull('reden')->orWhere('reden', ''))->count(),
        ];

        return $r;
    }
}
