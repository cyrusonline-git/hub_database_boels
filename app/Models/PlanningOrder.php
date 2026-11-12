<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class PlanningOrder extends Model
{
    protected $table = 'planning_orders';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['picked'=>'float','total'=>'float','delivery_date'=>'date:Y-m-d'];
}
