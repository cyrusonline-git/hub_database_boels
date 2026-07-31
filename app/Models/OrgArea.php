<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['business_unit_id', 'name', 'country', 'sort_order'];

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(OrgDepot::class, 'area_id')->orderBy('sort_order')->orderBy('name');
    }
}
