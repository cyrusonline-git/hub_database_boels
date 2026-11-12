<?php

namespace App\Console\Commands;

use App\Services\Sap\SapImportService;
use Illuminate\Console\Command;

/** php artisan sap:import /pad/naar/bestand.xlsx [--soort=wow] — zelfde verwerking als Beheer → Uploads SAP. */
class SapImport extends Command
{
    protected $signature = 'sap:import {pad : Pad naar het Excel-bestand} {--soort= : Verwachte soort (wow|factuurrun|planning|reserveringen)}';

    protected $description = 'SAP-export importeren (WOW, factuurrun, planning, reserveringen)';

    public function handle(SapImportService $service): int
    {
        $pad = (string) $this->argument('pad');
        if (! is_readable($pad)) {
            $this->error("Bestand niet leesbaar: $pad");

            return self::FAILURE;
        }
        $res = $service->importeer($pad, basename($pad), null, $this->option('soort') ?: null);
        $this->line(($res['ok'] ? 'OK  ' : 'FOUT ').$res['melding']);
        if (! empty($res['upload'])) {
            $this->line('    upload #'.$res['upload']->id.' · rapportdatum '.$res['upload']->rapportdatum?->format('d-m-Y').' · '.json_encode($res['upload']->samenvatting, JSON_UNESCAPED_UNICODE));
        }

        return $res['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
