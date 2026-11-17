<?php

namespace App\Services\Sap;

use App\Models\FleetItem;

/**
 * "Items With Every Status" — vlootvoorraad Industrial, gegroepeerd rapport:
 * groepregels "CURRDEPOT: 759" (kolom A) en "GRPCODE: 10228" (kolom B); itemregels
 * hebben het machinenummer in kolom C (kop ITEMNO staat in A) en verder
 * Item Description, GRPCODE, STATUS, CURRDEPOT, LOCATION onder hun kop.
 * Alle depots worden meegenomen (het is de hele Industrial-vloot). Regels zonder
 * GRPCODE (gebruikersnamen die Smartview meeleverde) worden overgeslagen.
 */
class VlootImporter
{
    public function importeer(array $rijen, int $uploadId, int $koprij, array $k): ImportResultaat
    {
        $r = new ImportResultaat;
        $r->rapportdatum = now()->toDateString();
        $data = array_slice($rijen, $koprij + 1);
        // Machinenummerkolom: kop ITEMNO, maar de waarde staat (gegroepeerd) twee kolommen verder.
        $itemKol = $k['itemno'];
        foreach ([2, 1, 0] as $sprong) {
            $hits = 0;
            foreach (array_slice($data, 0, 200) as $rij) {
                if (SapBestand::tekst($rij[$k['grpcode']] ?? null) && SapBestand::tekst($rij[$k['itemno'] + $sprong] ?? null)) {
                    $hits++;
                }
            }
            if ($hits > 0) {
                $itemKol = $k['itemno'] + $sprong;
                break;
            }
        }
        $kol = ['oms' => $k['item description'], 'grp' => $k['grpcode'], 'status' => $k['status'], 'depot' => $k['currdepot'], 'loc' => $k['location'] ?? null];
        $v = fn (array $rij, string $naam) => $kol[$naam] === null ? null : SapBestand::tekst($rij[$kol[$naam]] ?? null);

        $buffer = [];
        $gezien = [];
        $perStatus = array_fill_keys(array_keys(FleetItem::STATUSSEN), 0);
        $perDepot = [];
        $ruw = [];
        foreach ($data as $rij) {
            $item = SapBestand::tekst($rij[$itemKol] ?? null);
            $grp = $v($rij, 'grp');
            if (! $item || ! $grp || str_contains($item, ':')) {
                $r->overgeslagen++;
                continue;
            }
            if (isset($gezien[$item])) {          // dubbel machinenummer: eerste telt
                $r->overgeslagen++;
                continue;
            }
            $gezien[$item] = true;
            $status = $v($rij, 'status');
            $sk = FleetItem::statusKey($status);
            $depot = $v($rij, 'depot');
            $buffer[] = [
                'upload_id' => $uploadId,
                'item_number' => mb_substr($item, 0, 30),
                'item_description' => mb_substr((string) $v($rij, 'oms'), 0, 255) ?: null,
                'group_code' => mb_substr($grp, 0, 20),
                'status' => $status ? mb_substr($status, 0, 40) : null,
                'status_key' => $sk,
                'depot' => $depot ? mb_substr($depot, 0, 10) : null,
                'location' => ($l = $v($rij, 'loc')) ? mb_substr($l, 0, 30) : null,
            ];
            $r->regels++;
            $perStatus[$sk]++;
            $perDepot[$depot ?? '?'][$sk] = ($perDepot[$depot ?? '?'][$sk] ?? 0) + 1;
            if ($sk === 'overig') {
                $ruw[$status ?? '(leeg)'] = ($ruw[$status ?? '(leeg)'] ?? 0) + 1;
            }
            if (count($buffer) >= 1000) {
                FleetItem::insert($buffer);
                $buffer = [];
            }
        }
        if ($buffer) {
            FleetItem::insert($buffer);
        }
        ksort($perDepot);
        $r->contracten = count($perDepot);   // kolom "contracten/orders" = aantal depots
        $bezet = $r->regels ? round(100 * $perStatus['verhuurd'] / $r->regels, 1) : 0;
        $r->samenvatting = [
            'machines' => $r->regels,
            'depots' => count($perDepot),
            'verhuurd' => $perStatus['verhuurd'],
            'beschikbaar' => $perStatus['beschikbaar'],
            'onderhoud' => $perStatus['onderhoud'],
            'reparatie' => $perStatus['reparatie'],
            'onderweg' => $perStatus['onderweg'],
            'overig' => $perStatus['overig'],
            'bezetting_pct' => $bezet.'%',
            'per_depot' => $perDepot,          // arrays worden op de kaart niet getoond, wel voor trends/API
            'overig_statussen' => $ruw,
        ];
        if ($ruw) {
            $r->meldingen[] = 'Onbekende status(sen) als "Overig" geteld: '.implode(', ', array_map(fn ($s, $n) => "$s ($n)", array_keys($ruw), $ruw));
        }

        return $r;
    }
}
