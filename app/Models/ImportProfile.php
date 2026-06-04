<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'entity', 'description', 'default_mapping', 'created_by'];

    protected $casts = ['default_mapping' => 'array'];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function jobs(): HasMany { return $this->hasMany(ImportJob::class, 'profile_id'); }
}
