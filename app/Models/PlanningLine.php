<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Artikelregel uit de gegroepeerde 8.7-export (zie docs/sap-uploads.md). */
class PlanningLine extends Model
{
    protected $table = 'planning_lines';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['qty' => 'float', 'delivery_date' => 'date:Y-m-d'];
}
