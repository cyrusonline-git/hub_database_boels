<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'work_order_number', 'project_id', 'customer_id',
        'machine_id', 'assigned_employee_id', 'status',
        'description', 'planned_date', 'completed_at',
    ];

    protected $casts = [
        'planned_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(Employee::class, 'assigned_employee_id'); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }
}
