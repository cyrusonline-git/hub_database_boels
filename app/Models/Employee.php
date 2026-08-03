<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /**
     * Depotnamen uit de bron komen soms als "Geleen; Industrial…" (en door
     * het bronsysteem afgekapt op ±32 tekens). Alleen het deel vóór de ";"
     * is de echte depotnaam — normaliseer bij elke save/import.
     */
    public function setDepotAttribute($value): void
    {
        $clean = is_string($value) ? trim(explode(';', $value)[0]) : $value;
        $this->attributes['depot'] = $clean === '' ? null : $clean;
    }

    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'employee_number', 'name', 'email', 'phone',
        'department_id', 'function', 'active',
        'area', 'country', 'city', 'region', 'depot',
        'start_date', 'end_date', 'manager', 'cost_center',
        'external_id', 'source_system',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
