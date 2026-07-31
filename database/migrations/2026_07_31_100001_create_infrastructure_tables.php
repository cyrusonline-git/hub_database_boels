<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hiërarchie: business unit (Industrial) > area (West/Noord/Zuid/België) > depot
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('org_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('country', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_unit_id', 'name']);
        });

        Schema::create('org_depots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('org_areas')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 190)->nullable();
            $table->string('city', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['area_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_depots');
        Schema::dropIfExists('org_areas');
        Schema::dropIfExists('business_units');
    }
};
