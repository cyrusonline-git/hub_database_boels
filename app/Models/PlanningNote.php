<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** SAP-export (zie config/sap.php, docs/sap-uploads.md). */
class PlanningNote extends Model
{
    protected $table = 'planning_notes';

    public $timestamps = true;

    protected $fillable = ['order_number','expeditie_employee_id','opmerking','vlag','user_id'];

    protected $casts = ['vlag'=>'boolean'];
}
