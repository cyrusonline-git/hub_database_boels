<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class InvoiceRunLine extends Model
{
    protected $table = 'invoice_run_lines';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['quantity'=>'float','delivery_date'=>'date:Y-m-d','hire_start_date'=>'date:Y-m-d','hire_end_date'=>'date:Y-m-d','item_hire_start_date'=>'date:Y-m-d','coll_date'=>'date:Y-m-d','last_invoice_date'=>'date:Y-m-d'];
}
