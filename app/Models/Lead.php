<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'lead_number', 'name', 'source', 'status',
        'customer_id', 'assigned_to', 'expected_value', 'description',
    ];

    protected $casts = ['expected_value' => 'decimal:2'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(Employee::class, 'assigned_to'); }
}
