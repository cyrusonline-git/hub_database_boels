<?php

namespace App\Services\Sap;

/** Uitkomst van één importer: tellers + afgeleide gegevens voor sap_uploads. */
class ImportResultaat
{
    public int $regels = 0;
    public int $overgeslagen = 0;
    public int $contracten = 0;
    public ?string $rapportdatum = null;
    public array $samenvatting = [];
    public array $meldingen = [];
}
