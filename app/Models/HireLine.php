<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class HireLine extends Model
{
    protected $table = 'hire_lines';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['qty_out'=>'float','weekly_hire_rate'=>'float','week_rate'=>'float','discount'=>'float','waiver'=>'float','coll_doc_printed'=>'boolean','hire_date'=>'date:Y-m-d','delivery_date'=>'date:Y-m-d','est_return_date'=>'date:Y-m-d','coll_date'=>'date:Y-m-d'];
}
