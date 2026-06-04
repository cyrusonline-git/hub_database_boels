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
     * Voer de import daadwerkelijk uit.
     */
    public function run(ImportJob $job): void
    {
        $job->update(['status' => 'processing', 'started_at' => now()]);

        $config = EntityRegistry::get($job->profile?->entity ?? $job->mapping['__entity'] ?? '');
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

        $imported = 0; $failed = 0;

        foreach ($dataRows as $i => $row) {
            $rowNumber = $i + 2; // header is row 1
            $assoc = [];
            foreach ($headers as $colIdx => $header) {
                $field = $mapping[$header] ?? null;
                if (! $field) continue;
                $assoc[$field] = $row[$colIdx] ?? null;
            }

            $rowRecord = new ImportJobRow([
                'import_job_id' => $job->id,
                'row_number' => $rowNumber,
                'raw_data' => $assoc,
                'status' => 'pending',
            ]);

            try {
                DB::transaction(function () use ($model, $assoc, $uniqueKeys, $rowRecord) {
                    $where = [];
                    foreach ($uniqueKeys as $k) {
                        $where[$k] = $assoc[$k] ?? null;
                    }
                    $entity = $model::updateOrCreate($where, $assoc);
                    $rowRecord->status = 'imported';
                    $rowRecord->created_entity_id = $entity->getKey();
                    $rowRecord->created_entity_type = $model;
                    $rowRecord->save();
                });
                $imported++;
            } catch (\Throwable $e) {
                $rowRecord->status = 'error';
                $rowRecord->error_message = mb_substr($e->getMessage(), 0, 1000);
                $rowRecord->save();
                $failed++;
            }
        }

        $job->update([
            'status' => 'completed',
            'total_rows' => count($dataRows),
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'finished_at' => now(),
        ]);
    }
}
