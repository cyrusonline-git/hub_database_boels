<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'is_system', 'application_id'];

    protected $casts = ['is_system' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    /** null = platform-brede rol; gezet = rol geldt alleen binnen die app */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    /** Apps die deze rol in de launcher ziet */
    public function launcherApplications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_role')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }
}
