<?php

namespace App\Vraagbaak;

use Carbon\Carbon;

/**
 * Periode uit een vraag halen: vandaag, gisteren, deze/vorige week, week 40, deze/vorige maand,
 * september (2026), dit/vorig jaar, 2025, afgelopen 30 dagen, Q3, van 1-9 tot 15-9.
 * Geeft ['van' => Carbon, 'tm' => Carbon (einde dag), 'label' => 'september 2026', 'match' => gevonden tekst] of null.
 */
class Periode
{
    private const MAANDEN = ['januari' => 1, 'jan' => 1, 'februari' => 2, 'feb' => 2, 'maart' => 3, 'mrt' => 3, 'april' => 4, 'apr' => 4, 'mei' => 5, 'juni' => 6, 'jun' => 6, 'juli' => 7, 'jul' => 7, 'augustus' => 8, 'aug' => 8, 'september' => 9, 'sep' => 9, 'sept' => 9, 'oktober' => 10, 'okt' => 10, 'november' => 11, 'nov' => 11, 'december' => 12, 'dec' => 12];

    public static function nu(): Carbon
    {
        return Carbon::now('Europe/Amsterdam');
    }

    public static function herken(string $t, ?Carbon $nu = null): ?array
    {
        $nu = $nu ?? self::nu();
        $t = ' '.mb_strtolower($t).' ';
        $m = [];
        if (preg_match('/\b(van|vanaf|tussen)\s+(\d{1,2})[-\/](\d{1,2})(?:[-\/](\d{2,4}))?\s+(?:tot|t\/m|tm|en)\s+(\d{1,2})[-\/](\d{1,2})(?:[-\/](\d{2,4}))?/', $t, $m)) {
            $j1 = self::jaar($m[4] ?? '', $nu);
            $j2 = self::jaar($m[7] ?? '', $nu);
            $van = Carbon::create($j1, (int) $m[3], (int) $m[2], 0, 0, 0, 'Europe/Amsterdam');
            $tm = Carbon::create($j2, (int) $m[6], (int) $m[5], 0, 0, 0, 'Europe/Amsterdam')->endOfDay();

            return self::uit($van, $tm, $van->format('d-m-Y').' t/m '.$tm->format('d-m-Y'), $m[0]);
        }
        if (preg_match('/\b(afgelopen|laatste)\s+(\d+)\s+(dag|dagen|week|weken|maand|maanden)\b/', $t, $m)) {
            $n = (int) $m[2];
            $van = match (true) { str_starts_with($m[3], 'dag') => $nu->copy()->subDays($n), str_starts_with($m[3], 'week') => $nu->copy()->subWeeks($n), default => $nu->copy()->subMonths($n) };

            return self::uit($van->startOfDay(), $nu->copy()->endOfDay(), "afgelopen $n {$m[3]}", $m[0]);
        }
        if (preg_match('/\b(vandaag|gisteren|eergisteren)\b/', $t, $m)) {
            $d = match ($m[1]) { 'vandaag' => $nu->copy(), 'gisteren' => $nu->copy()->subDay(), default => $nu->copy()->subDays(2) };

            return self::uit($d->copy()->startOfDay(), $d->copy()->endOfDay(), $m[1].' ('.$d->format('d-m-Y').')', $m[0]);
        }
        if (preg_match('/\b(deze|vorige|afgelopen|volgende|komende)\s+(week|maand|jaar|kwartaal)\b/', $t, $m)) {
            $d = $nu->copy();
            $richting = match ($m[1]) { 'vorige', 'afgelopen' => -1, 'volgende', 'komende' => 1, default => 0 };
            [$van, $tm, $label] = match ($m[2]) {
                'week' => [$d->addWeeks($richting)->startOfWeek(), $d->copy()->endOfWeek(), 'week '.$d->format('W').' ('.$d->format('d-m').' t/m '.$d->copy()->endOfWeek()->format('d-m-Y').')'],
                'maand' => [$d->addMonthsNoOverflow($richting)->startOfMonth(), $d->copy()->endOfMonth(), self::maandNaam((int) $d->format('n')).' '.$d->format('Y')],
                'kwartaal' => [$d->addQuarters($richting)->startOfQuarter(), $d->copy()->endOfQuarter(), 'Q'.$d->quarter.' '.$d->format('Y')],
                default => [$d->addYears($richting)->startOfYear(), $d->copy()->endOfYear(), $d->format('Y')],
            };

            return self::uit($van, $tm->endOfDay(), $label, $m[0]);
        }
        if (preg_match('/\b(dit|huidig|huidige|lopend|lopende)\s+(jaar|kwartaal)\b/', $t, $m)) {
            return $m[2] === 'jaar' ? self::uit($nu->copy()->startOfYear(), $nu->copy()->endOfYear(), $nu->format('Y'), $m[0]) : self::uit($nu->copy()->startOfQuarter(), $nu->copy()->endOfQuarter(), 'Q'.$nu->quarter.' '.$nu->format('Y'), $m[0]);
        }
        if (preg_match('/\bweek\s*(\d{1,2})(?:\s*(?:van|in)?\s*(\d{4}))?\b/', $t, $m)) {
            $jaar = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : (int) $nu->format('o');
            $van = Carbon::now('Europe/Amsterdam')->setISODate($jaar, (int) $m[1], 1)->startOfDay();

            return self::uit($van, $van->copy()->addDays(6)->endOfDay(), 'week '.$m[1].' '.$jaar, $m[0]);
        }
        if (preg_match('/\b(q|kwartaal\s*)([1-4])(?:\s+(\d{4}))?\b/', $t, $m)) {
            $jaar = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) $nu->format('Y');
            $van = Carbon::create($jaar, ((int) $m[2] - 1) * 3 + 1, 1, 0, 0, 0, 'Europe/Amsterdam');

            return self::uit($van, $van->copy()->endOfQuarter()->endOfDay(), 'Q'.$m[2].' '.$jaar, $m[0]);
        }
        foreach (self::MAANDEN as $naam => $nr) {
            if (preg_match('/\b'.$naam.'\b(?:\s+(\d{4}))?/', $t, $m)) {
                $jaar = isset($m[1]) && $m[1] !== '' ? (int) $m[1] : (int) $nu->format('Y');
                // "september" zonder jaar en nog in de toekomst? Dan vorig jaar.
                if ((! isset($m[1]) || $m[1] === '') && $nr > (int) $nu->format('n')) {
                    $jaar--;
                }
                $van = Carbon::create($jaar, $nr, 1, 0, 0, 0, 'Europe/Amsterdam');

                return self::uit($van, $van->copy()->endOfMonth()->endOfDay(), self::maandNaam($nr).' '.$jaar, $m[0]);
            }
        }
        if (preg_match('/\b(20\d{2})\b/', $t, $m)) {
            $van = Carbon::create((int) $m[1], 1, 1, 0, 0, 0, 'Europe/Amsterdam');

            return self::uit($van, $van->copy()->endOfYear()->endOfDay(), $m[1], $m[0]);
        }
        if (preg_match('/\b(vorig|afgelopen)\s+jaar\b/', $t, $m)) {
            $d = $nu->copy()->subYear();

            return self::uit($d->copy()->startOfYear(), $d->copy()->endOfYear(), $d->format('Y'), $m[0]);
        }
        if (preg_match('/\b(altijd|ooit|totaal|alles|in totaal)\b/', $t, $m)) {
            return self::uit(Carbon::create(2000, 1, 1, 0, 0, 0, 'Europe/Amsterdam'), $nu->copy()->addYears(5), 'alle tijd', $m[0]);
        }

        return null;
    }

    /** Standaardperiode als de vraag er geen noemt. */
    public static function standaard(string $soort, ?Carbon $nu = null): array
    {
        $nu = $nu ?? self::nu();

        return match ($soort) {
            'jaar' => self::uit($nu->copy()->startOfYear(), $nu->copy()->endOfYear(), $nu->format('Y'), ''),
            'week' => self::uit($nu->copy()->startOfWeek(), $nu->copy()->endOfWeek(), 'week '.$nu->format('W'), ''),
            'geen' => self::uit(Carbon::create(2000, 1, 1, 0, 0, 0, 'Europe/Amsterdam'), $nu->copy()->addYears(5), 'alle tijd', ''),
            default => self::uit($nu->copy()->startOfMonth(), $nu->copy()->endOfMonth(), self::maandNaam((int) $nu->format('n')).' '.$nu->format('Y'), ''),
        };
    }

    private static function uit(Carbon $van, Carbon $tm, string $label, string $match): array
    {
        return ['van' => $van, 'tm' => $tm, 'label' => $label, 'match' => trim($match), 'van_s' => $van->format('Y-m-d'), 'tm_s' => $tm->format('Y-m-d')];
    }

    private static function jaar(string $j, Carbon $nu): int
    {
        if ($j === '') {
            return (int) $nu->format('Y');
        }

        return strlen($j) === 2 ? 2000 + (int) $j : (int) $j;
    }

    public static function maandNaam(int $n): string
    {
        return ['', 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'][$n] ?? '';
    }
}
