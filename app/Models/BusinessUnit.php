<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'sort_order'];

    public function areas(): HasMany
    {
        return $this->hasMany(OrgArea::class)->orderBy('sort_order')->orderBy('name');
    }
}
