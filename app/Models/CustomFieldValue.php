<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = ['custom_field_id', 'valuable_id', 'valuable_type', 'value'];

    public function customField(): BelongsTo { return $this->belongsTo(CustomField::class); }
    public function valuable(): MorphTo { return $this->morphTo(); }
}
