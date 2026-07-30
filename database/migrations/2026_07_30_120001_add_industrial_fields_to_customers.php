<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('second_name', 255)->nullable()->after('customer_name');
            $table->string('responsible', 150)->nullable()->after('second_name');
            $table->string('responsible_role', 150)->nullable()->after('responsible');
            $table->string('concern_number', 50)->nullable()->index()->after('responsible_role');
            $table->string('concern_name', 255)->nullable()->after('concern_number');
            $table->string('concern_responsible', 150)->nullable()->after('concern_name');
            $table->string('concern_responsible_role', 150)->nullable()->after('concern_responsible');
            $table->string('purchasing_org_number', 50)->nullable()->after('concern_responsible_role');
            $table->string('purchasing_org_name', 255)->nullable()->after('purchasing_org_number');
            $table->string('purchasing_org_responsible', 150)->nullable()->after('purchasing_org_name');
            $table->string('purchasing_org_responsible_role', 150)->nullable()->after('purchasing_org_responsible');
            $table->string('nace_code', 20)->nullable()->index()->after('purchasing_org_responsible_role');
            $table->string('nace_description', 255)->nullable()->after('nace_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'second_name', 'responsible', 'responsible_role',
                'concern_number', 'concern_name', 'concern_responsible', 'concern_responsible_role',
                'purchasing_org_number', 'purchasing_org_name',
                'purchasing_org_responsible', 'purchasing_org_responsible_role',
                'nace_code', 'nace_description',
            ]);
        });
    }
};
