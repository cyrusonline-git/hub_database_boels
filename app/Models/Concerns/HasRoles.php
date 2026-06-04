<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function assignRole(Role|int|string $role): void
    {
        $role = $this->resolveRole($role);
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function removeRole(Role|int|string $role): void
    {
        $role = $this->resolveRole($role);
        $this->roles()->detach($role->id);
    }

    public function syncRoles(array $roles): void
    {
        $ids = collect($roles)->map(fn ($r) => $this->resolveRole($r)->id)->all();
        $this->roles()->sync($ids);
    }

    public function hasRole(string|array $role): bool
    {
        $names = (array) $role;
        return $this->roles()->whereIn('slug', $names)->orWhereIn('name', $names)->exists();
    }

    public function hasPermission(string $key): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->permissions()->where('key', $key)->exists();
    }

    public function permissions(): Collection|\Illuminate\Database\Eloquent\Builder
    {
        return Permission::query()
            ->whereIn('id', function ($q) {
                $q->select('permission_id')
                    ->from('role_permissions')
                    ->whereIn('role_id', $this->roles()->select('roles.id'));
            });
    }

    protected function resolveRole(Role|int|string $role): Role
    {
        if ($role instanceof Role) {
            return $role;
        }
        if (is_int($role)) {
            return Role::findOrFail($role);
        }
        return Role::where('slug', $role)->orWhere('name', $role)->firstOrFail();
    }
}
