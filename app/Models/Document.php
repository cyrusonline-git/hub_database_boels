<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'documentable_id', 'documentable_type', 'title', 'category',
        'uploaded_by', 'file_path', 'mime_type', 'size_bytes',
    ];

    public function documentable(): MorphTo { return $this->morphTo(); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }
}
