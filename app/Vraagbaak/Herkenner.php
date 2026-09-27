<?php

namespace App\Vraagbaak;

/**
 * Van vrije tekst naar een catalogusvraag + parameters.
 * Scoren op woordgroepen (moet/bonus); parameters: depot/area, periode, top-N, brandstof, machinenummer, naam.
 */
class Herkenner
{
    public const STOP = ['hoeveel', 'wat', 'welke', 'welk', 'wie', 'waar', 'hoe', 'is', 'zijn', 'was', 'waren', 'heeft', 'hebben', 'had', 'de', 'het', 'een', 'er', 'in', 'op', 'bij', 'van', 'voor', 'met', 'aan', 'te', 'en', 'of', 'dit', 'deze', 'die', 'dat', 'ik', 'we', 'wij', 'ons', 'onze', 'nu', 'al', 'nog', 'ook', 'dan', 'toch', 'graag', 'even', 'maar', 'naar', 'over', 'per', 'door', 'om', 'tot', 'geef', 'laat', 'zien', 'toon'];

    public static function normaliseer(string $t): string
    {
        $t = mb_strtolower(trim($t));
        $t = strtr($t, ['é' => 'e', 'ë' => 'e', 'è' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u', 'á' => 'a']);
        $t = preg_replace('/[^\p{L}\p{N}\s\'\-\/]+/u', ' ', $t);

        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /** Woorden in de vraag (met eenvoudige stam: meervoud/-en/-s weg voor matching). */
    public static function woorden(string $norm): array
    {
        return array_values(array_filter(explode(' ', $norm), fn ($w) => $w !== ''));
    }

    private static function bevat(array $woorden, string $norm, string $term): bool
    {
        $term = mb_strtolower($term);
        if (str_contains($term, ' ')) {
            return str_contains(' '.$norm.' ', ' '.$term.' ') || str_contains($norm, $term);
        }
        foreach ($woorden as $w) {
            if ($w === $term || (mb_strlen($term) >= 5 && str_starts_with($w, $term)) || (mb_strlen($w) >= 5 && str_starts_with($term, $w) && mb_strlen($term) - mb_strlen($w) <= 2)) {
                return true;
            }
        }

        return false;
    }

    /** Score van een vraag voor deze tekst; 0 = uitgesloten. */
    public static function score(Vraag $v, string $norm, array $woorden): float
    {
        $score = 0;
        foreach ($v->moet as $groep) {
            $hit = false;
            foreach ((array) $groep as $term) {
                if (self::bevat($woorden, $norm, $term)) {
                    $hit = true;
                    $score += 2 + (str_contains($term, ' ') ? 1 : 0);
                    break;
                }
            }
            if (! $hit) {
                return 0;
            }
        }
        $bonus = 0;
        foreach ($v->bonus as $term) {
            if (self::bevat($woorden, $norm, $term)) {
                $bonus += 1;
            }
        }
        $score += min(2, $bonus);
        // Vragen die een naam of machinenummer nodig hebben, zakken als die ontbreekt
        if (in_array('naam', $v->params, true) && ! preg_match('/"[^"]{2,}"|\b(?:van|voor|door|bij)\s+[a-z][a-z\'\-]+\s+[a-z]/u', $norm)) {
            $score -= 1.5;
        }
        if (in_array('machine', $v->params, true) && ! preg_match('/\b\d{6,12}\b/', $norm)) {
            $score -= 1.5;
        }

        return $score;
    }

    /** Parameters uit de tekst. */
    public static function parameters(string $tekst, Vraag $v): array
    {
        $norm = self::normaliseer($tekst);
        $p = ['depot' => null, 'periode' => null, 'top' => null, 'brandstof' => null, 'machine' => null, 'naam' => null, 'nummer' => null];
        if (in_array('depot', $v->params, true)) {
            $p['depot'] = Depots::herken($tekst);
        }
        if (in_array('periode', $v->params, true)) {
            $p['periode'] = Periode::herken($tekst) ?? Periode::standaard($v->periode);
        }
        if (preg_match('/\btop\s*(\d{1,3})\b/', $norm, $m)) {
            $p['top'] = (int) $m[1];
        } elseif (preg_match('/\b(\d{1,3})\s+(grootste|meeste|hoogste|beste|slechtste)\b/', $norm, $m)) {
            $p['top'] = (int) $m[1];
        }
        if (preg_match('/\b(adblue|ad blue)\b/', $norm)) {
            $p['brandstof'] = 'AdBlue';
        } elseif (preg_match('/\brode\s+diesel\b/', $norm)) {
            $p['brandstof'] = 'Rode Diesel (BE)';
        } elseif (preg_match('/\bdiesel\b/', $norm)) {
            $p['brandstof'] = 'Diesel';
        }
        if (preg_match('/\b(\d{6,12})\b/', $norm, $m)) {
            $p['machine'] = $m[1];
        }
        if (preg_match('/\b(?:van|voor|door|bij)\s+([a-z][a-z\'\-]+(?:\s+(?:van|de|der|den|le|la)\s+)?[a-z][a-z\'\-]+)\b/u', $norm, $m)) {
            $p['naam'] = $m[1];
        }
        if (preg_match('/"([^"]{2,60})"/', $tekst, $m)) {
            $p['naam'] = $m[1];
        }

        return $p;
    }

    /**
     * Beste vraag zoeken. Geeft ['status' => 'ok'|'keuze'|'onbekend', 'vraag' => ?Vraag, 'params' => [], 'kandidaten' => [[id,titel,score]...]]
     */
    public static function herken(string $tekst, array $vragen): array
    {
        $norm = self::normaliseer($tekst);
        $woorden = self::woorden($norm);
        $scores = [];
        foreach ($vragen as $v) {
            $s = self::score($v, $norm, $woorden);
            if ($s > 0) {
                $scores[] = [$v, $s];
            }
        }
        usort($scores, fn ($a, $b) => $b[1] <=> $a[1]);
        $kandidaten = array_map(fn ($x) => ['id' => $x[0]->id, 'titel' => $x[0]->titel, 'bron' => $x[0]->bron, 'score' => $x[1]], array_slice($scores, 0, 5));
        if (! $scores) {
            return ['status' => 'onbekend', 'vraag' => null, 'params' => [], 'kandidaten' => self::dichtstbij($norm, $woorden, $vragen)];
        }
        $beste = $scores[0];
        $tweede = $scores[1][1] ?? 0;
        if ($tweede > 0 && $beste[1] - $tweede < 1 && count($scores) > 1) {
            return ['status' => 'keuze', 'vraag' => null, 'params' => [], 'kandidaten' => array_slice($kandidaten, 0, 4)];
        }

        return ['status' => 'ok', 'vraag' => $beste[0], 'params' => self::parameters($tekst, $beste[0]), 'kandidaten' => $kandidaten];
    }

    /** Bij geen match: vragen met de meeste overlappende bonuswoorden. */
    private static function dichtstbij(string $norm, array $woorden, array $vragen): array
    {
        $lijst = [];
        foreach ($vragen as $v) {
            $n = 0;
            foreach (array_merge(array_merge(...array_map(fn ($g) => (array) $g, $v->moet ?: [[]])), $v->bonus) as $term) {
                if (self::bevat($woorden, $norm, $term)) {
                    $n++;
                }
            }
            if ($n > 0) {
                $lijst[] = ['id' => $v->id, 'titel' => $v->titel, 'bron' => $v->bron, 'score' => $n];
            }
        }
        usort($lijst, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($lijst, 0, 4);
    }
}
