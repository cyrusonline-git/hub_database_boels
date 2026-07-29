<?php

namespace App\Services\Import;

use App\Models\Machine;
use App\Models\MachineGroup;
use App\Models\MachineSubgroup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import van de twee materieellijsten:
 *
 * 1. Subgroeplijst  — één rij per subgroep (kolom "Subgroep") met
 *    productgroep, omschrijving en ~250 sparse specificatie-kolommen.
 * 2. Unieke lijst   — één rij per uniek materieelnummer met zijn subgroep.
 *
 * Hiërarchie: Productgroep (machine_groups) > Subgroep (machine_subgroups)
 *             > uniek materieelnummer (machines).
 */
class MaterialListImportService
{
    /** Vaste kolommen van de subgroeplijst; al het overige wordt specificatie */
    private const FIXED_HEADERS = [
        'tabblad', 'categorie', 'toepassing', 'subgroep', 'merk', 'type',
        'productomschrijving', 'highlight 1', 'highlight 2', 'productgroep',
    ];

    public function importSubgroupList(string $storedPath): array
    {
        $rows = Excel::toArray([], Storage::path($storedPath))[0] ?? [];
        if (count($rows) < 2) {
            return ['error' => 'Het bestand bevat geen datarijen.'];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rows[0]);
        $lower = array_map('mb_strtolower', $headers);

        $subgroepCol = array_search('subgroep', $lower);
        if ($subgroepCol === false) {
            return ['error' => 'Kolom "Subgroep" niet gevonden in het bestand. Gevonden kolommen: '.implode(', ', array_slice($headers, 0, 10)).'…'];
        }

        $col = fn (string $name) => array_search($name, $lower);
        $fixed = [
            'tabblad' => $col('tabblad'),
            'categorie' => $col('categorie'),
            'toepassing' => $col('toepassing'),
            'merk' => $col('merk'),
            'type' => $col('type'),
            'productomschrijving' => $col('productomschrijving'),
            'productgroep' => $col('productgroep'),
        ];

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $groupCache = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNr = $i + 2;
            $subgroupNumber = $this->cleanNumber($row[$subgroepCol] ?? null);
            if ($subgroupNumber === '') {
                $skipped++;
                continue;
            }

            $get = fn ($idx) => $idx === false ? null : $this->cleanValue($row[$idx] ?? null);

            $productgroep = $get($fixed['productgroep']) ?: 'Overig';
            $omschrijving = $get($fixed['productomschrijving']) ?: ('Subgroep '.$subgroupNumber);

            // Specificaties: alle niet-vaste, niet-lege kolommen.
            // Accessoires / Verkoopartikelen / Alternatieven / Highlights → lijstjes.
            $specs = [];
            $lists = ['accessoires' => [], 'verkoopartikelen' => [], 'alternatieven' => [], 'highlights' => []];
            $serviceCodes = null;

            foreach ($headers as $idx => $header) {
                if ($idx === $subgroepCol || in_array($lower[$idx], self::FIXED_HEADERS)) {
                    continue;
                }
                $value = $this->cleanValue($row[$idx] ?? null);
                if ($value === null || $value === '' || $value === '.') {
                    continue;
                }
                $base = mb_strtolower(preg_replace('/\s*\d+$/', '', $header));
                if (isset($lists[$base])) {
                    $lists[$base][] = $value;
                } elseif ($lower[$idx] === 'service codes') {
                    $serviceCodes = $value;
                } else {
                    $specs[$header] = $value;
                }
            }
            // Highlight 1/2 zitten in de vaste kolommen maar horen in de lijst
            foreach (['highlight 1', 'highlight 2'] as $h) {
                $idx = $col($h);
                $v = $get($idx);
                if ($v) $lists['highlights'][] = $v;
            }

            try {
                $groupKey = mb_strtolower($productgroep);
                if (! isset($groupCache[$groupKey])) {
                    $groupCache[$groupKey] = MachineGroup::withTrashed()->firstOrCreate(
                        ['group_number' => Str::limit(Str::slug($productgroep), 50, '')],
                        ['group_name' => Str::limit($productgroep, 150)],
                    );
                    if ($groupCache[$groupKey]->trashed()) {
                        $groupCache[$groupKey]->restore();
                    }
                }
                $group = $groupCache[$groupKey];

                $subgroup = MachineSubgroup::withTrashed()->where('subgroup_number', $subgroupNumber)->first();
                $data = [
                    'group_id' => $group->id,
                    'subgroup_number' => $subgroupNumber,
                    'subgroup_name' => Str::limit($omschrijving, 150),
                    'tabblad' => $get($fixed['tabblad']),
                    'categorie' => $get($fixed['categorie']),
                    'toepassing' => $get($fixed['toepassing']),
                    'merk' => $get($fixed['merk']),
                    'type' => $get($fixed['type']),
                    'highlights' => $lists['highlights'] ?: null,
                    'specifications' => $specs ?: null,
                    'accessoires' => $lists['accessoires'] ?: null,
                    'verkoopartikelen' => $lists['verkoopartikelen'] ?: null,
                    'alternatieven' => $lists['alternatieven'] ?: null,
                    'service_codes' => $serviceCodes,
                ];

                if ($subgroup) {
                    if ($subgroup->trashed()) $subgroup->restore();
                    $subgroup->update($data);
                    $updated++;
                } else {
                    MachineSubgroup::create($data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Rij $rowNr (subgroep $subgroupNumber): ".$e->getMessage();
            }
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    public function importMachineList(string $storedPath): array
    {
        $rows = Excel::toArray([], Storage::path($storedPath))[0] ?? [];
        if (count($rows) < 2) {
            return ['error' => 'Het bestand bevat geen datarijen.'];
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);

        // Flexibele kolomherkenning
        $find = function (array $needles) use ($headers) {
            foreach ($headers as $idx => $h) {
                foreach ($needles as $n) {
                    if ($h !== '' && str_contains($h, $n)) return $idx;
                }
            }
            return false;
        };

        $nrCol = $find(['materieelnummer', 'materieelnr', 'machinenummer', 'uniek nummer', 'fleetnummer']);
        $sgCol = $find(['subgroep', 'subgroup']);
        if ($nrCol === false || $sgCol === false || $nrCol === $sgCol) {
            return ['error' => 'Kon de kolommen niet herkennen. Verwacht: een kolom met "Materieelnummer" en een kolom met "Subgroep". Gevonden: '.implode(', ', array_filter($headers))];
        }

        $descCol = $find(['omschrijving', 'productomschrijving', 'beschrijving']);
        $brandCol = $find(['merk']);
        $modelCol = $find(['type', 'model']);
        $serialCol = $find(['serienummer', 'serial']);
        $yearCol = $find(['bouwjaar', 'jaar']);
        $locCol = $find(['locatie', 'depot', 'vestiging']);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $unknownSubgroups = [];
        $subgroupCache = [];
        $fallbackGroup = null;

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNr = $i + 2;
            $machineNumber = $this->cleanNumber($row[$nrCol] ?? null);
            $subgroupNumber = $this->cleanNumber($row[$sgCol] ?? null);
            if ($machineNumber === '') {
                $skipped++;
                continue;
            }

            $get = fn ($idx) => $idx === false ? null : $this->cleanValue($row[$idx] ?? null);

            try {
                if (! isset($subgroupCache[$subgroupNumber])) {
                    $sg = $subgroupNumber === '' ? null
                        : MachineSubgroup::withTrashed()->where('subgroup_number', $subgroupNumber)->first();

                    if ($sg && $sg->trashed()) $sg->restore();

                    if (! $sg) {
                        // Subgroep nog niet bekend: maak placeholder aan zodat de
                        // upload nooit blokkeert; subgroeplijst vult hem later aan.
                        $fallbackGroup ??= MachineGroup::withTrashed()->firstOrCreate(
                            ['group_number' => 'onbekend'],
                            ['group_name' => 'Onbekend (nog geen subgroeplijst)'],
                        );
                        if ($fallbackGroup->trashed()) $fallbackGroup->restore();

                        $sg = MachineSubgroup::create([
                            'group_id' => $fallbackGroup->id,
                            'subgroup_number' => $subgroupNumber !== '' ? $subgroupNumber : '0',
                            'subgroup_name' => $subgroupNumber !== ''
                                ? "Subgroep $subgroupNumber (nog niet in subgroeplijst)"
                                : 'Zonder subgroep',
                        ]);
                        if ($subgroupNumber !== '') {
                            $unknownSubgroups[$subgroupNumber] = true;
                        }
                    }
                    $subgroupCache[$subgroupNumber] = $sg;
                }
                $subgroup = $subgroupCache[$subgroupNumber];

                $description = $get($descCol) ?: $subgroup->subgroup_name;
                $year = $get($yearCol);
                $year = ($year && preg_match('/^(19|20)\d{2}$/', $year)) ? (int) $year : null;

                $machine = Machine::withTrashed()->where('machine_number', $machineNumber)->first();
                $data = array_filter([
                    'subgroup_id' => $subgroup->id,
                    'description' => Str::limit($description, 255),
                    'brand' => $get($brandCol),
                    'model' => $get($modelCol),
                    'serial_number' => $get($serialCol),
                    'year' => $year,
                    'location' => $get($locCol),
                ], fn ($v) => $v !== null);
                $data['source_system'] = 'materieellijst-upload';

                if ($machine) {
                    if ($machine->trashed()) $machine->restore();
                    $machine->update($data);
                    $updated++;
                } else {
                    Machine::create(['machine_number' => $machineNumber] + $data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Rij $rowNr (materieelnummer $machineNumber): ".$e->getMessage();
            }
        }

        return compact('created', 'updated', 'skipped', 'errors') + [
            'unknown_subgroups' => array_keys($unknownSubgroups),
        ];
    }

    /** Nummers uit Excel komen soms als float (84183.0) — normaliseer naar string */
    private function cleanNumber(mixed $value): string
    {
        if ($value === null) return '';
        if (is_float($value) || is_int($value)) {
            return (string) (int) round((float) $value);
        }
        return trim((string) $value);
    }

    private function cleanValue(mixed $value): ?string
    {
        if ($value === null) return null;
        if (is_float($value) && floor($value) == $value) {
            return (string) (int) $value;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }
}
