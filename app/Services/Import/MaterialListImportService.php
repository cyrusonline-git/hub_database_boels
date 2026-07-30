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
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

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

    /**
     * Unieke materieellijst: Analysis group | Product group | Subgroep |
     * Unique number | Omschrijving. Kan 65k+ rijen bevatten, dus batch-upserts.
     */
    public function importMachineList(string $storedPath): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        $rows = Excel::toArray([], Storage::path($storedPath))[0] ?? [];
        if (count($rows) < 2) {
            return ['error' => 'Het bestand bevat geen datarijen.'];
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);

        // Flexibele kolomherkenning
        $find = function (array $needles) use ($headers) {
            foreach ($needles as $n) {
                foreach ($headers as $idx => $h) {
                    if ($h !== '' && str_contains($h, $n)) return $idx;
                }
            }
            return false;
        };

        $nrCol = $find(['unique number', 'uniek nummer', 'materieelnummer', 'materieelnr', 'machinenummer', 'fleetnummer', 'uniek']);
        $sgCol = $find(['subgroep', 'subgroup', 'sub group']);
        if ($nrCol === false || $sgCol === false || $nrCol === $sgCol) {
            return ['error' => 'Kon de kolommen niet herkennen. Verwacht: een kolom "Unique number"/"Materieelnummer" en een kolom "Subgroep". Gevonden: '.implode(', ', array_filter($headers))];
        }

        $agCol = $find(['analysis group', 'analysegroep', 'analyse groep']);
        $pgCol = $find(['product group', 'productgroep']);
        $descCol = $find(['omschrijving', 'productomschrijving', 'beschrijving', 'description']);

        $get = fn ($row, $idx) => $idx === false ? null : $this->cleanValue($row[$idx] ?? null);

        // ---- Pass 1: hiërarchie opbouwen (analysegroep > productgroep > subgroep)
        $skipped = 0;
        $hierarchy = []; // subgroup_number => ['pg' =>, 'ag' =>, 'name' =>]
        $machineRows = []; // machine_number => [subgroup_number, description]

        foreach (array_slice($rows, 1) as $row) {
            $machineNumber = $this->cleanNumber($row[$nrCol] ?? null);
            $subgroupNumber = $this->cleanNumber($row[$sgCol] ?? null);
            if ($machineNumber === '') {
                $skipped++;
                continue;
            }
            if ($subgroupNumber !== '' && ! isset($hierarchy[$subgroupNumber])) {
                $hierarchy[$subgroupNumber] = [
                    'pg' => $get($row, $pgCol) ?: 'Overig',
                    'ag' => $get($row, $agCol),
                    'name' => $get($row, $descCol) ?: "Subgroep $subgroupNumber",
                ];
            }
            $machineRows[$machineNumber] = [
                'sg' => $subgroupNumber,
                'desc' => $get($row, $descCol),
            ];
        }

        // Productgroepen aanmaken/bijwerken (incl. analysegroep erboven)
        $groupIds = []; // lowercase pg-naam => id
        foreach ($hierarchy as $info) {
            $key = mb_strtolower($info['pg']);
            if (isset($groupIds[$key])) continue;
            $group = MachineGroup::withTrashed()->firstOrCreate(
                ['group_number' => Str::limit(Str::slug($info['pg']), 50, '')],
                ['group_name' => Str::limit($info['pg'], 150)],
            );
            if ($group->trashed()) $group->restore();
            if ($info['ag'] && $group->analysis_group !== $info['ag']) {
                $group->update(['analysis_group' => Str::limit($info['ag'], 150)]);
            }
            $groupIds[$key] = $group->id;
        }

        // Subgroepen resolven: bestaande behouden (subgroeplijst is leidend voor
        // specs), placeholders/nieuwe krijgen de groep uit deze lijst.
        $subgroupIds = []; // subgroup_number => id
        $existing = MachineSubgroup::withTrashed()
            ->whereIn('subgroup_number', array_keys($hierarchy))
            ->get()->keyBy('subgroup_number');
        $newSubgroups = [];

        foreach ($hierarchy as $number => $info) {
            $sg = $existing[$number] ?? null;
            if ($sg) {
                if ($sg->trashed()) $sg->restore();
                $subgroupIds[$number] = $sg->id;
                if (str_contains($sg->subgroup_name, 'nog niet in subgroeplijst')) {
                    $sg->update([
                        'group_id' => $groupIds[mb_strtolower($info['pg'])],
                        'subgroup_name' => Str::limit($info['name'], 150),
                    ]);
                }
            } else {
                $newSubgroups[] = $number;
                $subgroupIds[$number] = MachineSubgroup::create([
                    'group_id' => $groupIds[mb_strtolower($info['pg'])],
                    'subgroup_number' => $number,
                    'subgroup_name' => Str::limit($info['name'], 150),
                ])->id;
            }
        }

        // Vangnet voor rijen zonder subgroep
        $noSubgroupId = null;
        if (collect($machineRows)->contains(fn ($r) => $r['sg'] === '')) {
            $fallbackGroup = MachineGroup::withTrashed()->firstOrCreate(
                ['group_number' => 'onbekend'],
                ['group_name' => 'Onbekend'],
            );
            if ($fallbackGroup->trashed()) $fallbackGroup->restore();
            $noSubgroupId = MachineSubgroup::withTrashed()->firstOrCreate(
                ['group_id' => $fallbackGroup->id, 'subgroup_number' => '0'],
                ['subgroup_name' => 'Zonder subgroep'],
            )->id;
        }

        // ---- Pass 2: machines in batches upserten
        $existingNumbers = [];
        Machine::withTrashed()->select('id', 'machine_number')
            ->chunkById(5000, function ($chunk) use (&$existingNumbers) {
                foreach ($chunk as $m) $existingNumbers[$m->machine_number] = true;
            });

        $created = 0;
        $updated = 0;
        $batch = [];
        $now = now();

        foreach ($machineRows as $number => $info) {
            $sgId = $info['sg'] !== '' ? ($subgroupIds[$info['sg']] ?? $noSubgroupId) : $noSubgroupId;
            if (! $sgId) { $skipped++; continue; }

            isset($existingNumbers[$number]) ? $updated++ : $created++;
            $batch[] = [
                'machine_number' => (string) $number,
                'description' => Str::limit($info['desc'] ?: 'Onbekend', 255),
                'subgroup_id' => $sgId,
                'source_system' => 'materieellijst-upload',
                'deleted_at' => null, // her-upload herstelt verwijderde nummers
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batch) >= 500) {
                Machine::upsert($batch, ['machine_number'], ['description', 'subgroup_id', 'source_system', 'deleted_at', 'updated_at']);
                $batch = [];
            }
        }
        if ($batch) {
            Machine::upsert($batch, ['machine_number'], ['description', 'subgroup_id', 'source_system', 'deleted_at', 'updated_at']);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => [],
            'unknown_subgroups' => $newSubgroups,
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
