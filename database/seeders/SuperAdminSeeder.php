<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('boels.superadmin.email');
        if (! $email) {
            $this->command?->warn('SUPERADMIN_EMAIL niet ingesteld — seeder overgeslagen.');
            return;
        }

        $password = config('boels.superadmin.password') ?: Str::random(16);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('boels.superadmin.name') ?: 'Super Admin',
                'password' => Hash::make($password),
                'is_super_admin' => true,
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superRole = Role::where('slug', 'super-admin')->first();
        if ($superRole) {
            $user->roles()->syncWithoutDetaching([$superRole->id]);
        }

        if (! config('boels.superadmin.password')) {
            $this->command?->warn("Tijdelijk wachtwoord voor {$email}: {$password}");
        }
    }
}
