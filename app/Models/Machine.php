<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'machine_number', 'description', 'subgroup_id',
        'brand', 'model', 'serial_number', 'year',
        'status', 'location', 'external_id', 'source_system',
    ];

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(MachineSubgroup::class, 'subgroup_id');
    }

    public function damages(): HasMany
    {
        return $this->hasMany(Damage::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'valuable');
    }
}
