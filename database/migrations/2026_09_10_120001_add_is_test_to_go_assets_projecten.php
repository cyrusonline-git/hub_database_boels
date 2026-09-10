<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testmodus in de Go-Assets aanvraagtool: testaanvragen (ref TEST-JJJJ-NNN)
 * worden gemarkeerd zodat ze herkenbaar zijn en in één keer opgeruimd
 * kunnen worden zonder de echte GA-reeks te raken.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('go_assets_projecten', 'is_test')) {
            Schema::table('go_assets_projecten', function (Blueprint $table) {
                $table->boolean('is_test')->default(false)->index()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('go_assets_projecten', 'is_test')) {
            Schema::table('go_assets_projecten', function (Blueprint $table) {
                $table->dropColumn('is_test');
            });
        }
    }
};
