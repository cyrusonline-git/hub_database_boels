<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Afkoopprijs per subgroep (Import Engine → "Afkoopprijzen"). De mutators vangen de vorm van de lijst op:
 * "12345 - omschrijving" → omschrijving, "op aanvraag" → on_request, "27.200000000000003" / "1.234,56" → bedrag.
 */
class BuyoutPrice extends Model
{
    use SoftDeletes;

    protected $table = 'buyout_prices';

    protected $fillable = ['subgroup', 'type', 'description', 'price', 'on_request', 'price_raw', 'source_system'];

    protected $casts = ['price' => 'float', 'on_request' => 'boolean'];

    public function setSubgroupAttribute($v): void
    {
        $s = trim((string) $v);
        $this->attributes['subgroup'] = preg_match('/^\d+(\.0+)?$/', $s) ? (string) (int) $s : $s;
    }

    public function setDescriptionAttribute($v): void
    {
        $s = trim((string) $v);
        $this->attributes['description'] = $s === '' ? null : mb_substr(preg_replace('/^\s*\d+\s*-\s*/u', '', $s), 0, 255);
    }

    public function setPriceAttribute($v): void
    {
        $raw = trim((string) $v);
        $this->attributes['price_raw'] = $raw === '' ? null : mb_substr($raw, 0, 50);
        $s = str_replace(['€', ' '], '', $raw);
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace(['.', ','], ['', '.'], $s);      // 1.234,56
        } elseif (preg_match('/^-?\d+,\d+$/', $s)) {
            $s = str_replace(',', '.', $s);                   // 1234,56
        }
        if ($s !== '' && is_numeric($s)) {
            $this->attributes['price'] = round((float) $s, 2);
            $this->attributes['on_request'] = false;
        } else {
            $this->attributes['price'] = null;
            $this->attributes['on_request'] = $raw !== '';    // "op aanvraag" (of andere tekst)
        }
    }

    /** Afkoopprijs bij een machine- óf subgroepnummer: subgroep direct, anders via de machinelijst of de vloot. */
    public static function voorNummer(?string $nummer, ?string $subgroep = null): array
    {
        $nr = strtoupper(preg_replace('/[\s\-]+/', '', trim((string) $nummer)));
        $sub = trim((string) $subgroep);
        $bron = $sub !== '' ? 'subgroep' : null;
        if ($sub === '' && $nr !== '') {
            if (static::where('subgroup', $nr)->exists()) {
                [$sub, $bron] = [$nr, 'subgroep'];
            } elseif ($m = Machine::with('subgroup')->where('machine_number', $nr)->first()) {
                [$sub, $bron] = [(string) $m->subgroup?->subgroup_number, 'machinelijst'];
            } else {
                $vl = SapUpload::laatste('vloot');
                $g = $vl ? FleetItem::where('upload_id', $vl->id)->where('item_number', $nr)->value('group_code') : null;
                [$sub, $bron] = $g ? [(string) $g, 'vloot'] : ['', null];
            }
        }
        $p = $sub !== '' ? static::where('subgroup', $sub)->first() : null;

        return [
            'nummer' => $nr ?: null,
            'subgroep' => $sub ?: null,
            'subgroep_bron' => $bron,
            'gevonden' => (bool) $p,
            'type' => $p?->type,
            'omschrijving' => $p?->description,
            'afkoopprijs' => $p?->price,
            'op_aanvraag' => (bool) $p?->on_request,
            'lijst_bijgewerkt' => static::max('updated_at'),
        ];
    }
}
