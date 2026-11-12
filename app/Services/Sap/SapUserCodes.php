<?php

namespace App\Services\Sap;

use App\Models\Employee;
use App\Models\SapUserCode;
use Illuminate\Support\Collection;

/**
 * SAP-gebruikerscode → medewerker. Regel (Wim, 12-11-2026): eerste (max 5)
 * letters van de achternaam zonder tussenvoegsels + eerste letter van de
 * voornaam: Wouter Zwackhalen = ZWACKW, Martijn Kwak = KWAKM, Wilco van Bergen
 * = BERGEW. Namen die SAP voluit schrijft (8.7 "Jeroen Giessen") worden via
 * dezelfde code gematcht, zodat "Jeroen van der Giessen" in CORE ook klopt.
 * Resultaten staan in sap_user_codes (auto/handmatig) en zijn in Beheer te corrigeren.
 */
class SapUserCodes
{
    private const TUSSEN = ['van', 'de', 'der', 'den', 'het', 'ten', 'ter', 'te', 'v', 'd', 'vd', 'v/d', 'la', 'le', 'du', 'von', 'op', 'in', "'t", 'af'];

    /** @var array<string, ?int> cache per aanroep: code => employee_id */
    private array $cache = [];

    /** @var Collection<int, array{id:int, code:string, naam:string}>|null */
    private ?Collection $medewerkers = null;

    public static function normaliseer(string $s): string
    {
        $s = \Normalizer::isNormalized($s, \Normalizer::FORM_D) ? $s : \Normalizer::normalize($s, \Normalizer::FORM_D);
        $s = preg_replace('/\p{Mn}+/u', '', $s) ?? $s;   // accenten weg

        return strtoupper(preg_replace('/[^A-Za-z]/', '', $s) ?? '');
    }

    /** "Wilco van Bergen" → "BERGEW"; "Jeroen van der Giessen" → "GIESSJ"; null als niet te bepalen. */
    public static function codeVoorNaam(?string $naam): ?string
    {
        $naam = trim((string) $naam);
        if ($naam === '' || ! str_contains($naam, ' ')) {
            return null;
        }
        // "Achternaam, Voornaam" → omdraaien
        if (str_contains($naam, ',')) {
            [$a, $v] = array_map('trim', explode(',', $naam, 2));
            $naam = $v.' '.$a;
        }
        $delen = preg_split('/\s+/', $naam) ?: [];
        $voornaam = array_shift($delen);
        $rest = array_values(array_filter($delen, fn ($p) => ! in_array(mb_strtolower(trim($p, '.')), self::TUSSEN, true)));
        if (! $rest) {
            return null;
        }
        $achter = self::normaliseer(implode('', $rest));
        $voor = self::normaliseer($voornaam);
        if ($achter === '' || $voor === '') {
            return null;
        }

        return substr($achter, 0, 5).substr($voor, 0, 1);
    }

    /** Is $invoer een SAP-code (BERGEW, kwakm, dijksd1) en geen naam? */
    public static function isCode(string $invoer): bool
    {
        return (bool) preg_match('/^[A-Za-z]{3,7}\d?$/', trim($invoer)) && ! str_contains(trim($invoer), ' ');
    }

    /**
     * Medewerker-id voor een SAP-code of -naam; onthoudt het resultaat in sap_user_codes.
     * Handmatige koppelingen winnen altijd van de automatische.
     */
    public function employeeId(?string $invoer): ?int
    {
        $invoer = trim((string) $invoer);
        if ($invoer === '') {
            return null;
        }
        $sleutel = self::isCode($invoer) ? strtoupper($invoer) : 'NAAM:'.self::normaliseer($invoer);
        if (array_key_exists($sleutel, $this->cache)) {
            return $this->cache[$sleutel];
        }
        $rij = SapUserCode::firstOrNew(['code' => $sleutel]);
        if (! $rij->exists) {
            $rij->weergave = $invoer;
            $rij->bron = 'onbekend';
        }
        if ($rij->bron !== 'handmatig' && ! $rij->employee_id) {
            $id = self::isCode($invoer) ? $this->zoekOpCode(strtoupper($invoer)) : $this->zoekOpNaam($invoer);
            if ($id) {
                $rij->employee_id = $id;
                $rij->bron = 'auto';
            }
        }
        $rij->gezien = ($rij->gezien ?? 0) + 1;
        $rij->laatst_gezien_at = now();
        $rij->save();

        return $this->cache[$sleutel] = $rij->employee_id;
    }

    /** Kandidaten voor het beheerscherm: medewerkers wier code overeenkomt, anders op achternaam-prefix. */
    public function kandidaten(string $code): Collection
    {
        $code = strtoupper(trim($code));
        if (str_starts_with($code, 'NAAM:')) {
            $norm = substr($code, 5);

            return $this->lijst()->filter(fn ($m) => self::normaliseer($m['naam']) === $norm || str_contains(self::normaliseer($m['naam']), substr($norm, 0, 6)))->values();
        }
        $exact = $this->lijst()->filter(fn ($m) => $m['code'] === $code)->values();
        if ($exact->isNotEmpty()) {
            return $exact;
        }
        $prefix = preg_replace('/\d+$/', '', $code);
        $ach = substr($prefix, 0, -1);

        return $this->lijst()->filter(fn ($m) => $ach !== '' && $m['code'] !== null && str_starts_with($m['code'], substr($ach, 0, 4)))->values();
    }

    private function zoekOpCode(string $code): ?int
    {
        $code = preg_replace('/\d+$/', '', $code);          // dijksd1 → DIJKSD
        $kandidaten = $this->lijst()->filter(fn ($m) => $m['code'] === $code);
        if ($kandidaten->count() !== 1) {
            // Kortere achternamen (KWAKM): code zonder voorletter moet de hele genormaliseerde achternaam zijn
            return null;
        }

        return (int) $kandidaten->first()['id'];
    }

    private function zoekOpNaam(string $naam): ?int
    {
        $norm = self::normaliseer($naam);
        $exact = $this->lijst()->filter(fn ($m) => self::normaliseer($m['naam']) === $norm);
        if ($exact->count() === 1) {
            return (int) $exact->first()['id'];
        }
        $code = self::codeVoorNaam($naam);
        if (! $code) {
            return null;
        }
        $opCode = $this->lijst()->filter(fn ($m) => $m['code'] === $code);

        return $opCode->count() === 1 ? (int) $opCode->first()['id'] : null;
    }

    private function lijst(): Collection
    {
        return $this->medewerkers ??= Employee::query()
            ->select(['id', 'name', 'active'])
            ->orderByDesc('active')
            ->get()
            ->map(fn (Employee $e) => ['id' => $e->id, 'naam' => (string) $e->name, 'code' => self::codeVoorNaam($e->name)]);
    }
}
