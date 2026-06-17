<?php

namespace App\Services\Import;

use App\Models\FieldAlias;
use App\Models\ImportJob;
use App\Models\ImportJobRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService
{
    /**
     * Lees headers en preview-rows uit een geüpload bestand.
     */
    public function readHeadersAndPreview(string $storedPath, int $previewRows = 5): array
    {
        $rows = Excel::toArray([], Storage::path($storedPath))[0] ?? [];
        $headers = array_map(fn ($h) => is_string($h) ? trim($h) : (string) $h, $rows[0] ?? []);
        $preview = array_slice($rows, 1, $previewRows);

        return [
            'headers' => $headers,
            'preview' => $preview,
            'total_rows' => max(0, count($rows) - 1),
        ];
    }

    /**
     * Stel een initiële mapping voor op basis van field_aliases.
     */
    public function suggestMapping(string $entity, array $headers): array
    {
        $mapping = [];
        foreach ($headers as $i => $header) {
            $field = FieldAlias::resolve($entity, $header);
            if ($field) {
                $mapping[$header] = $field;
            } else {
                $direct = strtolower(str_replace(' ', '_', trim($header)));
                $config = EntityRegistry::get($entity);
                if ($config && isset($config['fields'][$direct])) {
                    $mapping[$header] = $direct;
                }
            }
        }
        return $mapping;
    }

    /**
     * Sla nieuwe / gewijzigde aliassen op zodat ze volgende keer automatisch gebruikt worden.
     */
    public function rememberMapping(string $entity, array $mapping, int $userId): void
    {
        foreach ($mapping as $alias => $field) {
            if (empty($field)) {
                continue;
            }
            $existing = FieldAlias::resolve($entity, $alias);
            if ($existing !== $field) {
                FieldAlias::updateOrCreate(
                    ['entity' => $entity, 'alias' => $alias],
                    ['field' => $field, 'created_by' => $userId],
                );
            }
        }
    }

    /**
     * Voer de import uit. Ondersteunt sync-mode: niet-voorkomende
     * records worden op active=0 gezet (soft-delete).
     */
    public function run(ImportJob $job): void
    {
        $job->update(['status' => 'processing', 'started_at' => now()]);

        $entity = $job->profile?->entity ?? $job->mapping['__entity'] ?? '';
        $config = EntityRegistry::get($entity);
        if (! $config) {
            $job->update(['status' => 'failed', 'error_log' => 'Onbekende entiteit', 'finished_at' => now()]);
            return;
        }

        $rows = Excel::toArray([], Storage::path($job->file_path))[0] ?? [];
        $headers = array_map(fn ($h) => trim((string) $h), $rows[0] ?? []);
        $dataRows = array_slice($rows, 1);

        $mapping = $job->mapping['fields'] ?? [];
        $uniqueKeys = $config['unique_keys'];
        $model = $config['model'];
        $autoGenerateFrom = $config['auto_generate_employee_number_from'] ?? null;

        $imported = 0; $failed = 0;
        $processedIds = [];   // bijgehouden voor sync-mode

        foreach ($dataRows as $i => $row) {
            $rowNumber = $i + 2; // header is row 1
            $assoc = [];
            foreach ($headers as $colIdx => $header) {
                $field = $mapping[$header] ?? null;
                if (! $field) continue;
                $value = $row[$colIdx] ?? null;
                // Lege strings → null (anders schrijft hij "" weg)
                if (is_string($value) && trim($value) === '') $value = null;
                $assoc[$field] = $value;
            }

            // Auto-generate employee_number uit email als die leeg is
            if ($entity === 'employee'
                && empty($assoc['employee_number'])
                && ! empty($assoc[$autoGenerateFrom ?? ''])
            ) {
                $assoc['employee_number'] = 'AUTO-' . substr(md5($assoc[$autoGenerateFrom]), 0, 12);
            }

            // Skip helemaal lege rijen
            if (empty(array_filter($assoc, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $rowRecord = new ImportJobRow([
                'import_job_id' => $job->id,
                'row_number' => $rowNumber,
                'raw_data' => $assoc,
                'status' => 'pending',
            ]);

            try {
                $entityRow = DB::transaction(function () use ($model, $assoc, $uniqueKeys, $rowRecord) {
                    // Vind het eerste unique_key veld dat in assoc een waarde heeft
                    $where = [];
                    foreach ($uniqueKeys as $k) {
                        if (! empty($assoc[$k])) {
                            $where[$k] = $assoc[$k];
                            break;
                        }
                    }
                    if (empty($where)) {
                        throw new \RuntimeException('Geen unique key gevuld (employee_number/email leeg)');
                    }

                    // Zorg dat actief=1 bij re-import (auto-reactivate)
                    if (array_key_exists('active', (new $model)->getAttributes()) || in_array('active', (new $model)->getFillable())) {
                        $assoc['active'] = $assoc['active'] ?? 1;
                    }

                    // Trashed record terughalen + reactivate
                    $existing = $model::withTrashed()->where($where)->first();
                    if ($existing && $existing->trashed()) {
                        $existing->restore();
                    }

                    $row = $model::updateOrCreate($where, $assoc);
                    $rowRecord->status = 'imported';
                    $rowRecord->created_entity_id = $row->getKey();
                    $rowRecord->created_entity_type = $model;
                    $rowRecord->save();
                    return $row;
                });
                $imported++;
                $processedIds[] = $entityRow->getKey();
            } catch (\Throwable $e) {
                $rowRecord->status = 'error';
                $rowRecord->error_message = mb_substr($e->getMessage(), 0, 1000);
                $rowRecord->save();
                $failed++;
            }
        }

        // ============ SYNC MODE: deactiveer wat niet in deze import zat ============
        $deactivated = 0;
        if ($job->sync_mode && ! empty($processedIds)) {
            $query = $model::query()->whereNotIn('id', $processedIds);

            // Alleen records die nog actief zijn worden gedeactiveerd
            if (in_array('active', (new $model)->getFillable())) {
                $query->where('active', true);
                $deactivated = $query->count();
                $query->update(['active' => false]);
            }

            // Optioneel: ook soft-delete
            $toDelete = $model::query()
                ->whereNotIn('id', $processedIds)
                ->whereNull('deleted_at')
                ->get();
            foreach ($toDelete as $rec) {
                $rec->delete();
            }
        }

        $job->update([
            'status' => 'completed',
            'total_rows' => count($dataRows),
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'deactivated_rows' => $deactivated,
            'finished_at' => now(),
        ]);
    }
}
