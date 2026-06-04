<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldAlias extends Model
{
    use HasFactory;

    protected $fillable = ['entity', 'alias', 'field', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function resolve(string $entity, string $alias): ?string
    {
        return static::where('entity', $entity)
            ->whereRaw('LOWER(alias) = ?', [mb_strtolower(trim($alias))])
            ->value('field');
    }
}
