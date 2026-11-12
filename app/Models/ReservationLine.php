<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class ReservationLine extends Model
{
    protected $table = 'reservation_lines';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['qty'=>'float','goods_value'=>'float','is_contract'=>'boolean','transport'=>'boolean','unconfirmed_collection'=>'boolean','delivery_date'=>'date:Y-m-d','hire_start_date'=>'date:Y-m-d','hire_end_date'=>'date:Y-m-d'];
}
