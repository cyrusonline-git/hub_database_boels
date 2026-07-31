<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDepot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['area_id', 'name', 'email', 'city', 'sort_order'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(OrgArea::class, 'area_id');
    }
}
