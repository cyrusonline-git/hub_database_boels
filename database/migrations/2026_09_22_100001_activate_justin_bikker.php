<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Account van Justin Bikker stond nog op 'wacht op activatie' terwijl hij al werkt (Wim, 22-09-2026). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereRaw('LOWER(email) = ?', ['justin.bikker@boels.nl'])
            ->where('status', '!=', 'active')
            ->update(['status' => 'active', 'active' => true, 'activation_token' => null, 'activation_token_expires_at' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
