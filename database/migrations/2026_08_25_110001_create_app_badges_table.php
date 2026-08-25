<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notificatie-tellers per app per gebruiker, voor het bolletje op de
        // dashboard-tegel. Child-apps melden een absoluut aantal via
        // POST /api/badge (met hun sync_key); CORE toont het alleen.
        Schema::create('app_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();
            $table->unique(['application_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_badges');
    }
};
