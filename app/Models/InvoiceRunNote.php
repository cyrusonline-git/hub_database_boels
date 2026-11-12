<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class InvoiceRunNote extends Model
{
    protected $table = 'invoice_run_notes';

    public $timestamps = true;

    protected $fillable = ['contract_number','reden','status','user_id','eerst_gezien','laatst_gezien','verdwenen_op'];

    protected $casts = ['eerst_gezien'=>'date:Y-m-d','laatst_gezien'=>'date:Y-m-d','verdwenen_op'=>'date:Y-m-d'];
}
