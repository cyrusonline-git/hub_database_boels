<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_number', 50)->unique();
            $table->string('project_name', 255)->index();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status', 50)->default('planning')->index();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('project_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('external_id', 100)->nullable()->index();
            $table->string('source_system', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
