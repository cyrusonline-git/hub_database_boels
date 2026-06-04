<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number', 50)->unique();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 50)->default('open')->index();
            $table->text('description')->nullable();
            $table->dateTime('planned_date')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('damages', function (Blueprint $table) {
            $table->id();
            $table->string('damage_number', 50)->unique();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('damage_date');
            $table->text('description');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->string('status', 50)->default('reported')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->dateTime('visit_date')->index();
            $table->string('purpose', 255)->nullable();
            $table->text('outcome')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number', 50)->unique();
            $table->string('name', 200);
            $table->string('source', 100)->nullable();
            $table->string('status', 50)->default('new')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('expected_value', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('opportunity_number', 50)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('name', 255);
            $table->string('stage', 50)->default('prospect');
            $table->decimal('amount', 12, 2)->nullable();
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('close_date')->nullable();
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->morphs('taskable');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 50)->default('open')->index();
            $table->string('priority', 20)->default('normal');
            $table->dateTime('due_date')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('customer_visits');
        Schema::dropIfExists('damages');
        Schema::dropIfExists('work_orders');
    }
};
