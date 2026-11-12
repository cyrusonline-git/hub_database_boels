<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class SapUserCode extends Model
{
    protected $table = 'sap_user_codes';

    public $timestamps = true;

    protected $fillable = ['code','weergave','employee_id','bron','gezien','laatst_gezien_at'];

    protected $casts = ['laatst_gezien_at'=>'datetime:Y-m-d H:i:s'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
