<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id', 'user_id', 'original_filename', 'file_path',
        'status', 'mapping', 'total_rows', 'imported_rows',
        'failed_rows', 'error_log', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'mapping' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function profile(): BelongsTo { return $this->belongsTo(ImportProfile::class, 'profile_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function rows(): HasMany { return $this->hasMany(ImportJobRow::class); }
}
