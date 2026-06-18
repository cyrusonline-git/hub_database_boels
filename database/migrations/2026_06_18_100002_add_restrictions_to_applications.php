<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->json('restricted_to_areas')->nullable()->after('active');
            $table->json('restricted_to_depots')->nullable()->after('restricted_to_areas');
            $table->json('restricted_to_countries')->nullable()->after('restricted_to_depots');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['restricted_to_areas', 'restricted_to_depots', 'restricted_to_countries']);
        });
    }
};
