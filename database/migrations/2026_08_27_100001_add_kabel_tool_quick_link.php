<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zet de Kabelcalculator ("Welke kabel?") als handige link op het
 * dashboard, onder Rekentools — naast de Generator Adviestool.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('quick_links')->where('url', '/tools/kabel')->exists();
        if (! $exists) {
            DB::table('quick_links')->insert([
                'title'       => 'Kabelcalculator',
                'url'         => '/tools/kabel',
                'icon'        => 'bi-plug',
                'category'    => 'Rekentools',
                'description' => 'Welke verlengkabel? Spanningsval gecontroleerd',
                'sort_order'  => 2,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('quick_links')->where('url', '/tools/kabel')->delete();
    }
};
