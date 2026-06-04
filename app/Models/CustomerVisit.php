<?php

namespace App\Models;

use App\Models\Concerns\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerVisit extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'customer_id', 'contact_id', 'employee_id',
        'visit_date', 'purpose', 'outcome', 'next_action',
    ];

    protected $casts = ['visit_date' => 'datetime'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
