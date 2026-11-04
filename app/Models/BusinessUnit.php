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

    /** Medewerkerkaarten van deze BU (employees.business_unit_id). */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /** BU's op volgorde, voor dropdowns (import, medewerkerkaart, filters). */
    public static function opties()
    {
        return static::orderBy('sort_order')->orderBy('name')->get();
    }

    public static function industrial(): ?self
    {
        return static::where('name', 'Industrial')->first();
    }
}
