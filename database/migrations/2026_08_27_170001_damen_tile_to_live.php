<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Livegang Damen Scan: tegel actief en URL van de testversie (/v2) naar
 * de echte app.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')->where('slug', 'damen')->update([
            'url'        => 'https://damen.sorai.nl',
            'active'     => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'damen')->update([
            'url'    => 'https://damen.sorai.nl/v2',
            'active' => false,
        ]);
    }
};
