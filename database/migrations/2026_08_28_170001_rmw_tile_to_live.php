<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RMW-livegang: de tegel wijst voortaan naar de live app (root)
 * in plaats van de v2-testversie.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')->where('slug', 'rmw')->update([
            'url'        => 'https://rmw.sorai.nl',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'rmw')->update(['url' => 'https://rmw.sorai.nl/v2']);
    }
};
