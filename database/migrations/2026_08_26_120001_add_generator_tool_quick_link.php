<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zet de Generator Adviestool (overgenomen uit de inhuur-app) als
 * handige link op het dashboard, onder de categorie Rekentools.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('quick_links')->where('url', '/tools/generator')->exists();
        if (! $exists) {
            DB::table('quick_links')->insert([
                'title'       => 'Generator Adviestool',
                'url'         => '/tools/generator',
                'icon'        => 'bi-lightning-charge',
                'category'    => 'Rekentools',
                'description' => 'Bereken het benodigde aggregaat (kVA / kW / A)',
                'sort_order'  => 1,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('quick_links')->where('url', '/tools/generator')->delete();
    }
};
