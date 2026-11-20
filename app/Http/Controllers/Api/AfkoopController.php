<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BuyoutPrice;
use App\Models\ImportJob;
use Illuminate\Http\Request;

/**
 * Afkoopprijzen (read-only) — de lijst "Afkoopprijzen industrial v(mmjjjj)" komt via de Import Engine
 * (entiteit buyout_price) in tabel buyout_prices. "op aanvraag" → prijs null + op_aanvraag true.
 * De versie komt uit de bestandsnaam van de laatst geslaagde import (bv. "…v082026-1.xlsx" → "08-2026").
 */
class AfkoopController extends Controller
{
    public function index(Request $r)
    {
        $q = BuyoutPrice::query()->orderBy('subgroup');
        $lijst = array_values(array_filter(array_map(fn ($s) => self::norm($s), explode(',', (string) $r->input('subgroups', '')))));
        if ($lijst) {
            $q->whereIn('subgroup', $lijst);
        }

        return ['versie' => self::versie(), 'data' => $q->get()->map(fn ($p) => self::regel($p))->values()];
    }

    public function show(string $subgroep)
    {
        $p = BuyoutPrice::where('subgroup', self::norm($subgroep))->first();
        abort_unless($p, 404, 'Geen afkoopprijs voor deze subgroep.');

        return ['versie' => self::versie()] + self::regel($p);
    }

    public static function regel(BuyoutPrice $p): array
    {
        return [
            'subgroup' => (string) $p->subgroup,
            'type' => $p->type,
            'omschrijving' => $p->description,
            'prijs' => $p->on_request || $p->price === null ? null : round((float) $p->price, 2),
            'op_aanvraag' => (bool) $p->on_request,
        ];
    }

    /** "08-2026" uit de bestandsnaam van de laatste geslaagde afkoopprijzen-import (null als onbekend). */
    public static function versie(): ?string
    {
        $jobs = ImportJob::where('status', 'completed')->orderByDesc('id')->limit(200)->get(['id', 'original_filename', 'mapping']);
        foreach ($jobs as $j) {
            if (($j->mapping['__entity'] ?? null) !== 'buyout_price') {
                continue;
            }
            if (preg_match('/(?:^|[^0-9])(0[1-9]|1[0-2])[-_. ]?(20\d{2})(?![0-9])/', (string) $j->original_filename, $m)) {
                return $m[1].'-'.$m[2];
            }

            return null;
        }

        return null;
    }

    private static function norm(?string $s): string
    {
        $s = trim((string) $s);

        return preg_match('/^\d+(\.0+)?$/', $s) ? (string) (int) $s : $s;
    }
}
