<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'opportunity_number', 'customer_id', 'name', 'stage',
        'amount', 'probability', 'close_date', 'owner_employee_id', 'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'probability' => 'integer',
        'close_date' => 'date',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function owner(): BelongsTo { return $this->belongsTo(Employee::class, 'owner_employee_id'); }
}
