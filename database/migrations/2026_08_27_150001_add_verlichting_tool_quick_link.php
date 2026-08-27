<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zet de Verlichtingscalculator ("Welke verlichting?") als handige link
 * op het dashboard, onder Rekentools — naast de generator- en kabeltool.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('quick_links')->where('url', '/tools/verlichting')->exists();
        if (! $exists) {
            DB::table('quick_links')->insert([
                'title'       => 'Verlichtingscalculator',
                'url'         => '/tools/verlichting',
                'icon'        => 'bi-lightbulb',
                'category'    => 'Rekentools',
                'description' => 'Welke verlichting? Lichtmasten, bouwlampen en 42V',
                'sort_order'  => 3,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('quick_links')->where('url', '/tools/verlichting')->delete();
    }
};
