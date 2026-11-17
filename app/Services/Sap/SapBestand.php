<?php

namespace App\Services\Sap;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Excel-lezer + normalisatie voor de SAP-rapporten. Leest .xls (BIFF, Crystal
 * Reports) en .xlsx, geeft rijen als platte arrays (0-based kolommen) terug en
 * zet SAP-notaties om: "1,00" → 1.0, "dd-mm-jjjj" / Excel-serial → datum,
 * "30-12-1899" → null.
 */
class SapBestand
{
    /** @return array<int, array<int, mixed>> */
    public static function rijen(string $pad): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');
        // Grote .xlsx (bv. vlootvoorraad, ~70.000 rijen): streamend lezen — PhpSpreadsheet kost daar
        // ~50 s en ~400 MB. Kleine bestanden blijven via PhpSpreadsheet gaan (bewezen gedrag).
        if (filesize($pad) > 1024 * 1024 && self::isXlsx($pad)) {
            $rijen = self::xlsxStream($pad);
            if ($rijen !== null) {
                return $rijen;
            }
        }
        $reader = IOFactory::createReaderForFile($pad);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($pad)->getSheet(0);
        $rijen = [];
        foreach ($sheet->toArray(null, true, false, false) as $rij) {
            // helemaal lege rij overslaan
            $leeg = true;
            foreach ($rij as $v) {
                if ($v !== null && trim((string) $v) !== '') {
                    $leeg = false;
                    break;
                }
            }
            if (! $leeg) {
                $rijen[] = $rij;
            }
        }

        return $rijen;
    }

    private static function isXlsx(string $pad): bool
    {
        $f = @fopen($pad, 'rb');
        $sig = $f ? fread($f, 4) : '';
        $f && fclose($f);

        return $sig === "PK\x03\x04" && class_exists(\ZipArchive::class) && class_exists(\XMLReader::class);
    }

    /**
     * Eerste werkblad van een .xlsx streamend lezen (XMLReader); zelfde uitvoer als rijen():
     * 0-based kolommen, ruwe waarden (getallen als int/float, datums als Excel-serial), lege rijen weg.
     * null = niet gelukt → terugval op PhpSpreadsheet.
     */
    private static function xlsxStream(string $pad): ?array
    {
        $zip = new \ZipArchive;
        if ($zip->open($pad) !== true) {
            return null;
        }
        try {
            // Eerste werkblad: workbook.xml → r:id → Target in workbook.xml.rels
            $blad = 'xl/worksheets/sheet1.xml';
            $wb = (string) $zip->getFromName('xl/workbook.xml');
            $rels = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
            if (preg_match('/<(?:\w+:)?sheet\b[^>]*\br:id="([^"]+)"/', $wb, $m)) {
                foreach (preg_split('/(?=<Relationship\b)/', $rels) as $rel) {
                    if (str_contains($rel, 'Id="'.$m[1].'"') && preg_match('/Target="([^"]+)"/', $rel, $t)) {
                        $doel = ltrim($t[1], '/');
                        $blad = str_starts_with($doel, 'xl/') ? $doel : 'xl/'.$doel;
                    }
                }
            }
            if ($zip->locateName($blad) === false) {
                return null;
            }
            // Gedeelde teksten
            $ss = [];
            if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                $r = new \XMLReader;
                $r->XML($xml);
                $huidig = null;
                while ($r->read()) {
                    if ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 'si') {
                        $huidig = '';
                    } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 'rPh') {
                        $r->next(); // fonetische hints overslaan
                    } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 't' && $huidig !== null) {
                        $huidig .= $r->readString();
                    } elseif ($r->nodeType === \XMLReader::END_ELEMENT && $r->localName === 'si') {
                        $ss[] = $huidig;
                        $huidig = null;
                    }
                }
                $r->close();
                unset($xml);
            }
            $kolomIndex = function (string $ref): int {
                $n = 0;
                foreach (str_split(preg_replace('/[^A-Z]/', '', $ref)) as $ch) {
                    $n = $n * 26 + (ord($ch) - 64);
                }

                return $n - 1;
            };
            $r = new \XMLReader;
            if (! $r->open('zip://'.$pad.'#'.$blad)) {
                return null;
            }
            $rijen = [];
            $rij = null;
            while ($r->read()) {
                if ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 'row') {
                    $rij = $r->isEmptyElement ? null : [];
                } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 'c' && $rij !== null) {
                    $ref = (string) $r->getAttribute('r');
                    $type = (string) $r->getAttribute('t');
                    $col = $ref !== '' ? $kolomIndex($ref) : count($rij);
                    $waarde = null;
                    if (! $r->isEmptyElement) {
                        $diepte = $r->depth;
                        $v = null;
                        $inline = '';
                        while ($r->read() && ! ($r->nodeType === \XMLReader::END_ELEMENT && $r->localName === 'c' && $r->depth === $diepte)) {
                            if ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 'v') {
                                $v = $r->readString();
                            } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 't') {
                                $inline .= $r->readString();
                            }
                        }
                        $waarde = match ($type) {
                            's' => $v === null ? null : ($ss[(int) $v] ?? null),
                            'inlineStr' => $inline,
                            'str', 'e' => $v,
                            'b' => $v === null ? null : (bool) $v,
                            default => $v === null || $v === '' ? null : (is_numeric($v) ? (preg_match('/^-?\d+$/', $v) ? (int) $v : (float) $v) : $v),
                        };
                    }
                    $rij[$col] = $waarde;
                } elseif ($r->nodeType === \XMLReader::END_ELEMENT && $r->localName === 'row' && $rij !== null) {
                    $gevuld = array_filter($rij, fn ($w) => $w !== null && trim((string) $w) !== '');
                    if ($gevuld) {
                        $vol = array_fill(0, max(array_keys($rij)) + 1, null);
                        foreach ($rij as $c => $w) {
                            $vol[$c] = $w;
                        }
                        $rijen[] = $vol;
                    }
                    $rij = null;
                }
            }
            $r->close();

            return $rijen;
        } catch (\Throwable $e) {
            report($e);

            return null;
        } finally {
            $zip->close();
        }
    }

    public static function tekst(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    /** "1,00" → 1.0 · "4270,63" → 4270.63 · "1.638,00" → 1638.0 · 12.5 → 12.5 */
    public static function getal(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        $s = trim((string) $v);
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {      // 1.638,00
            $s = str_replace('.', '', $s);
        }
        if (str_contains($s, ',') && ! str_contains($s, '.')) {       // 4270,63
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',') && str_contains($s, '.')) {  // 1,638.00
            $s = str_replace(',', '', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    /** Datum uit Excel-serial, DateTime of tekst (dd-mm-jjjj, d-m-jj, jjjj-mm-dd). 30-12-1899 / 01-01-1900 = leeg. */
    public static function datum(mixed $v): ?Carbon
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            if ($v instanceof \DateTimeInterface) {
                $d = Carbon::instance($v);
            } elseif (is_int($v) || is_float($v)) {
                if ($v < 100) {
                    return null;
                }
                $d = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v));
            } else {
                $s = trim((string) $v);
                if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/', $s, $m)) {
                    $j = (int) $m[3];
                    $j = $j < 100 ? 2000 + $j : $j;
                    if ((int) $m[2] > 12 || (int) $m[1] > 31) {
                        return null;
                    }
                    $d = Carbon::create($j, (int) $m[2], (int) $m[1]);
                } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
                    $d = Carbon::create((int) $m[1], (int) $m[2], (int) $m[3]);
                } elseif (is_numeric($s)) {
                    $d = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $s));
                } else {
                    return null;
                }
            }
        } catch (\Throwable) {
            return null;
        }
        if ($d->year < 1950) {
            return null;
        }

        return $d->startOfDay();
    }

    /** Tijd als "HH:MM" uit tekst, Excel-fractie of DateTime. */
    public static function tijd(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('H:i');
        }
        if (is_float($v) || is_int($v)) {
            $frac = fmod((float) $v, 1.0);
            if ($frac <= 0) {
                return null;
            }
            $min = (int) round($frac * 1440);

            return sprintf('%02d:%02d', intdiv($min, 60) % 24, $min % 60);
        }
        $s = trim((string) $v);

        return preg_match('/^(\d{1,2})[:.](\d{2})/', $s, $m) ? sprintf('%02d:%02d', (int) $m[1], (int) $m[2]) : null;
    }

    public static function ja(mixed $v): bool
    {
        $s = mb_strtolower(trim((string) $v));

        return ! in_array($s, ['', '0', '0.0', 'nee', 'no', 'false', 'n'], true);
    }

    /** Kopregel zoeken: eerste rij (binnen de eerste 5) waarin alle gezochte koppen voorkomen. Geeft [rijindex, kolom => index]. */
    public static function kop(array $rijen, array $gezocht, int $maxRijen = 5): ?array
    {
        $norm = fn ($s) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $s)));
        foreach (array_slice($rijen, 0, $maxRijen, true) as $i => $rij) {
            $map = [];
            foreach ($rij as $c => $v) {
                $k = $norm($v);
                if ($k !== '' && ! isset($map[$k])) {
                    $map[$k] = $c;
                }
            }
            $ok = true;
            foreach ($gezocht as $g) {
                if (! isset($map[$norm($g)])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return [$i, $map];
            }
        }

        return null;
    }

    /** Meest voorkomende datum in een lijst; null als die minder dan $drempel van de gevulde waarden dekt. */
    public static function dominanteDatum(array $datums, float $drempel = 0.4): ?string
    {
        $tel = [];
        $n = 0;
        foreach ($datums as $d) {
            if ($d instanceof Carbon) {
                $k = $d->toDateString();
                $tel[$k] = ($tel[$k] ?? 0) + 1;
                $n++;
            }
        }
        if (! $tel) {
            return null;
        }
        arsort($tel);
        $top = array_key_first($tel);

        return $tel[$top] / max(1, $n) >= $drempel ? $top : null;
    }
}
