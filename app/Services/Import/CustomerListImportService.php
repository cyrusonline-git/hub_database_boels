<?php

namespace App\Services\Import;

use App\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import van de Industrial klantenlijst:
 * Debtor | Debtor name | Debtor second name | Debtor responsible | Role |
 * Concern | Concern name | Concern responsible | Role |
 * Purchasing organisation (+ name/responsible/role) | NACE CODE | omschrijving.
 *
 * Sleutel = Debtor (klantnummer). "-" telt als leeg.
 */
class CustomerListImportService
{
    public function import(string $storedPath): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $rows = Excel::toArray([], Storage::path($storedPath))[0] ?? [];
        if (count($rows) < 2) {
            return ['error' => 'Het bestand bevat geen datarijen.'];
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);

        $find = function (array $needles) use ($headers) {
            foreach ($needles as $n) {
                foreach ($headers as $idx => $h) {
                    if ($h !== '' && str_contains($h, $n)) return $idx;
                }
            }
            return false;
        };

        $cols = [
            'customer_number' => $find(['debtor']),
            'customer_name' => $find(['debtor name', 'debiteur naam', 'klantnaam']),
            'second_name' => $find(['second name', 'tweede naam']),
            'responsible' => $find(['debtor responsible', 'debiteur verantwoordelijke']),
            'responsible_role' => $find(['role debtor', 'rol debiteur']),
            'concern_number' => $find(['concern']),
            'concern_name' => $find(['concern name', 'concern naam']),
            'concern_responsible' => $find(['concern responsible']),
            'concern_responsible_role' => $find(['role concern']),
            'purchasing_org_number' => $find(['purchasing organisation']),
            'purchasing_org_name' => $find(['purchasing organisation name']),
            'purchasing_org_responsible' => $find(['purchasing organisation responsible']),
            'purchasing_org_responsible_role' => $find(['role purchasing']),
            'nace_code' => $find(['nace']),
            'nace_description' => $find(['layer 4', 'nace omschrijving', 'nace description']),
        ];

        if ($cols['customer_number'] === false || $cols['customer_name'] === false) {
            return ['error' => 'Kon de kolommen niet herkennen. Verwacht minimaal "Debtor" en "Debtor name". Gevonden: '.implode(', ', array_filter($headers))];
        }

        // "Debtor" matcht ook "Debtor name" etc. — bij overlap eerste exacte kolom kiezen
        foreach (['customer_number' => 'debtor', 'concern_number' => 'concern', 'purchasing_org_number' => 'purchasing organisation'] as $field => $exact) {
            $i = array_search($exact, $headers);
            if ($i !== false) $cols[$field] = $i;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNr = $i + 2;

            $get = function (string $field) use ($cols, $row) {
                $idx = $cols[$field];
                if ($idx === false) return null;
                $v = $row[$idx] ?? null;
                if ($v === null) return null;
                if (is_float($v) && floor($v) == $v) $v = (int) $v;
                $s = trim((string) $v);
                return ($s === '' || $s === '-') ? null : $s;
            };

            $number = $get('customer_number');
            if ($number === null) {
                $skipped++;
                continue;
            }

            try {
                $data = [
                    'customer_name' => Str::limit($get('customer_name') ?? ('Klant '.$number), 255),
                    'second_name' => $get('second_name'),
                    'responsible' => $get('responsible'),
                    'responsible_role' => $get('responsible_role'),
                    'concern_number' => $get('concern_number'),
                    'concern_name' => $get('concern_name'),
                    'concern_responsible' => $get('concern_responsible'),
                    'concern_responsible_role' => $get('concern_responsible_role'),
                    'purchasing_org_number' => $get('purchasing_org_number'),
                    'purchasing_org_name' => $get('purchasing_org_name'),
                    'purchasing_org_responsible' => $get('purchasing_org_responsible'),
                    'purchasing_org_responsible_role' => $get('purchasing_org_responsible_role'),
                    'nace_code' => $get('nace_code'),
                    'nace_description' => $get('nace_description'),
                    'source_system' => 'klantenlijst-industrial',
                ];

                $customer = Customer::withTrashed()->where('customer_number', $number)->first();
                if ($customer) {
                    if ($customer->trashed()) $customer->restore();
                    $customer->update($data);
                    $updated++;
                } else {
                    Customer::create(['customer_number' => $number] + $data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Rij $rowNr (debiteur $number): ".$e->getMessage();
            }
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }
}
