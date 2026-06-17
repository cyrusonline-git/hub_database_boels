<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->boolean('sync_mode')->default(false)->after('mapping');
            $table->integer('deactivated_rows')->default(0)->after('failed_rows');
            $table->integer('reactivated_rows')->default(0)->after('deactivated_rows');
        });
    }

    public function down(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropColumn(['sync_mode', 'deactivated_rows', 'reactivated_rows']);
        });
    }
};
