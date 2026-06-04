<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'url',
        'icon', 'color', 'sort_order', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Application $app) {
            if (empty($app->slug)) {
                $app->slug = Str::slug($app->name);
            }
            if (empty($app->color)) {
                $app->color = config('boels.brand.color');
            }
        });
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
