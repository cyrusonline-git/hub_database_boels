<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MachineSubgroup extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = ['group_id', 'subgroup_number', 'subgroup_name', 'description'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MachineGroup::class, 'group_id');
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class, 'subgroup_id');
    }
}
