<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** PO-bewaking: handmatige status per contract zonder klantreferentie (zie docs/sap-uploads.md). */
class ReferenceNote extends Model
{
    protected $table = 'reference_notes';

    protected $fillable = ['contract_number', 'customer_name', 'status', 'opmerking', 'opgevraagd_op', 'user_id', 'eerst_gezien', 'laatst_gezien', 'verdwenen_op'];

    protected $casts = ['opgevraagd_op' => 'date:Y-m-d', 'eerst_gezien' => 'date:Y-m-d', 'laatst_gezien' => 'date:Y-m-d', 'verdwenen_op' => 'date:Y-m-d'];
}
