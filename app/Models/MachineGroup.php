<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MachineGroup extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = ['group_number', 'group_name', 'analysis_group', 'description'];

    public function subgroups(): HasMany
    {
        return $this->hasMany(MachineSubgroup::class, 'group_id');
    }
}
