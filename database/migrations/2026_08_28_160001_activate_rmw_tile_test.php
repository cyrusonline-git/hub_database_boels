<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RMW-tegel aanzetten voor de testfase — de URL blijft naar /v2 wijzen
 * tot de livegang (dan gaat hij naar de root).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')->where('slug', 'rmw')->update([
            'active'     => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'rmw')->update(['active' => false]);
    }
};
