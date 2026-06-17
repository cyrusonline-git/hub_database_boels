<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('area', 100)->nullable()->after('function');
            $table->string('country', 100)->nullable()->after('area');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('region', 100)->nullable()->after('city');
            $table->date('start_date')->nullable()->after('region');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('manager', 200)->nullable()->after('end_date');
            $table->string('cost_center', 50)->nullable()->after('manager');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['area', 'country', 'city', 'region',
                'start_date', 'end_date', 'manager', 'cost_center']);
        });
    }
};
