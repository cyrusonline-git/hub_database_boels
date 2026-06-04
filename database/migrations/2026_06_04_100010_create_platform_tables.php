<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 100)->index();
            $table->string('alias', 190);
            $table->string('field', 100);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['entity', 'alias']);
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 100)->index();
            $table->string('key', 100);
            $table->string('label', 150);
            $table->string('type', 30)->default('text');
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['entity', 'key']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->morphs('valuable');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['custom_field_id', 'valuable_type', 'valuable_id'], 'cfv_unique');
        });

        Schema::create('import_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('entity', 100)->index();
            $table->text('description')->nullable();
            $table->json('default_mapping')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained('import_profiles')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('original_filename', 255);
            $table->string('file_path', 500);
            $table->string('status', 30)->default('pending')->index();
            $table->json('mapping')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->longText('error_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_job_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('raw_data');
            $table->string('status', 30)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('created_entity_id')->nullable();
            $table->string('created_entity_type', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('auditable');
            $table->string('event', 50)->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('import_job_rows');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('import_profiles');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('field_aliases');
    }
};
