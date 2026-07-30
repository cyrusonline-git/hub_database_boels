<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Rol kan nu app-specifiek zijn; null = platform-brede rol
            $table->foreignId('application_id')->nullable()->after('slug')
                ->constrained('applications')->nullOnDelete();
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
            $table->unique(['application_id', 'name']);
            $table->unique(['application_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'name']);
            $table->dropUnique(['application_id', 'slug']);
            $table->dropConstrainedForeignId('application_id');
            $table->unique('name');
            $table->unique('slug');
        });
    }
};
