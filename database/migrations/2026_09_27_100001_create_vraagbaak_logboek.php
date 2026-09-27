<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vraagbaak_logboek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('vraag', 500);
            $table->string('herkend_als', 80)->nullable()->index();
            $table->string('status', 20)->default('ok');
            $table->json('parameters')->nullable();
            $table->unsignedInteger('duur_ms')->nullable();
            $table->string('feedback', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vraagbaak_logboek');
    }
};
