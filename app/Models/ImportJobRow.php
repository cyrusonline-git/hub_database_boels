<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJobRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_job_id', 'row_number', 'raw_data', 'status',
        'error_message', 'created_entity_id', 'created_entity_type',
    ];

    protected $casts = ['raw_data' => 'array'];

    public function job(): BelongsTo { return $this->belongsTo(ImportJob::class, 'import_job_id'); }
}
