<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'title', 'description', 'taskable_id', 'taskable_type',
        'assigned_to', 'status', 'priority', 'due_date', 'completed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function taskable(): MorphTo { return $this->morphTo(); }
    public function assignee(): BelongsTo { return $this->belongsTo(Employee::class, 'assigned_to'); }
}
