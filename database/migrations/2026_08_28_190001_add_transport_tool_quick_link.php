<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zet de Transportcalculator Europoort als handige link op het dashboard,
 * onder een eigen kop "Calculators" (los van de Rekentools).
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('quick_links')->where('url', '/tools/transport')->exists();
        if (! $exists) {
            DB::table('quick_links')->insert([
                'title'       => 'Transportcalculator Europoort',
                'url'         => '/tools/transport',
                'icon'        => 'bi-truck',
                'category'    => 'Calculators',
                'description' => 'Bereken transportkosten vanaf Europoort',
                'sort_order'  => 1,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('quick_links')->where('url', '/tools/transport')->delete();
    }
};
