<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_links', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('url', 500);
            $table->string('icon', 50)->nullable();      // bootstrap-icons klasse, bv. bi-calculator
            $table->string('category', 50)->nullable();  // bv. Rekentools, Documenten, Links
            $table->string('description', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_links');
    }
};
