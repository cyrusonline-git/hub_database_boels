<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',    'slug' => 'super-admin',    'is_system' => true,  'desc' => 'Volledige toegang tot alles.'],
            ['name' => 'Administrator',  'slug' => 'administrator',  'is_system' => true,  'desc' => 'Beheert gebruikers, rollen en applicaties.'],
            ['name' => 'User',           'slug' => 'user',           'is_system' => true,  'desc' => 'Standaard ingelogde gebruiker.'],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], [
                'name' => $r['name'],
                'description' => $r['desc'],
                'is_system' => $r['is_system'],
            ]);
        }

        // Administrator krijgt alle .manage permissies van platform-apps
        $admin = Role::where('slug', 'administrator')->first();
        $admin->permissions()->sync(Permission::pluck('id')->all());

        // Standaard "User" krijgt view-rechten op CORE Launcher
        $user = Role::where('slug', 'user')->first();
        $user->permissions()->sync(
            Permission::where('key', 'core.view')->pluck('id')->all()
        );
    }
}
