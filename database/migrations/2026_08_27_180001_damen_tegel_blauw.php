<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Damen-tegel in Damen-blauw: klantportalen krijgen een blauwe tegel
 * zodat het verschil met de interne (oranje) apps direct zichtbaar is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')->where('slug', 'damen')->update([
            'color'      => '#009FE3',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'damen')->update(['color' => '#FF6600']);
    }
};
