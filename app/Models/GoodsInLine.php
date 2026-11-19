<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Smartview 0.05 "Goods in to be off hired" (zie config/sap.php). */
class GoodsInLine extends Model
{
    protected $table = 'goods_in_lines';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['qty' => 'float', 'delivery_date' => 'date:Y-m-d', 'hire_start_date' => 'date:Y-m-d', 'hire_end_date' => 'date:Y-m-d', 'collection_date' => 'date:Y-m-d', 'goods_in_date' => 'date:Y-m-d'];
}
