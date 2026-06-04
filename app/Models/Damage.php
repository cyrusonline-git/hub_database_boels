<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Damage extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'damage_number', 'machine_id', 'reported_by',
        'customer_id', 'project_id', 'damage_date',
        'description', 'estimated_cost', 'actual_cost', 'status',
    ];

    protected $casts = [
        'damage_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(Employee::class, 'reported_by'); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }
}
