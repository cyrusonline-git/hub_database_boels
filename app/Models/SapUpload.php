<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class SapUpload extends Model
{
    protected $table = 'sap_uploads';

    public $timestamps = true;

    protected $fillable = ['soort','bestandsnaam','rapportdatum','user_id','regels','overgeslagen','contracten','hash','samenvatting','regels_opgeruimd'];

    protected $casts = ['rapportdatum'=>'date:Y-m-d','samenvatting'=>'array','regels_opgeruimd'=>'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Vingerafdruk van de stand van de data: verandert bij elke nieuwe upload (30 s gecached). */
    public static function stand(): string
    {
        return \Illuminate\Support\Facades\Cache::remember('sap:stand', 30, fn () => (string) (static::max('id') ?? 0));
    }

    /** Laatste upload per soort (null als er nog nooit iets is geüpload). */
    public static function laatste(string $soort): ?self
    {
        return static::where('soort', $soort)->orderByDesc('id')->first();
    }
}
