<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDepot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['area_id', 'name', 'number', 'email', 'city', 'sort_order'];

    /** Depotnummers als lijst ("384, 769" → ['384', '769']). */
    public function numbers(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->number))));
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(OrgArea::class, 'area_id');
    }
}
