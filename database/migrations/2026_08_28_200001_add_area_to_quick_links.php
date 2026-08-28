<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Handige links kunnen regio-gebonden zijn: `area` leeg = zichtbaar voor
 * iedereen; gevuld (bv. 'West') = alleen voor medewerkers van die area
 * (beheerders zien altijd alles). De Transportcalculator Europoort wordt
 * meteen op regio West gezet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_links', function (Blueprint $table) {
            $table->string('area', 50)->nullable()->after('category');
        });

        DB::table('quick_links')->where('url', '/tools/transport')->update([
            'area'       => 'West',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('quick_links', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
