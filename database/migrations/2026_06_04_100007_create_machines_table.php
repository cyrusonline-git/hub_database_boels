<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_number', 50)->unique();
            $table->string('group_name', 150);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('machine_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('machine_groups')->cascadeOnDelete();
            $table->string('subgroup_number', 50);
            $table->string('subgroup_name', 150);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['group_id', 'subgroup_number']);
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('machine_number', 50)->unique();
            $table->string('description', 255);
            $table->foreignId('subgroup_id')->constrained('machine_subgroups')->restrictOnDelete();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->year('year')->nullable();
            $table->string('status', 50)->default('available')->index();
            $table->string('location', 150)->nullable();
            $table->string('external_id', 100)->nullable()->index();
            $table->string('source_system', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
        Schema::dropIfExists('machine_subgroups');
        Schema::dropIfExists('machine_groups');
    }
};
