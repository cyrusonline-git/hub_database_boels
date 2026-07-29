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

    protected $fillable = [
        'group_id', 'subgroup_number', 'subgroup_name', 'description',
        'tabblad', 'categorie', 'toepassing', 'merk', 'type',
        'highlights', 'specifications', 'accessoires',
        'verkoopartikelen', 'alternatieven', 'service_codes',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'specifications' => 'array',
            'accessoires' => 'array',
            'verkoopartikelen' => 'array',
            'alternatieven' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MachineGroup::class, 'group_id');
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class, 'subgroup_id');
    }
}
