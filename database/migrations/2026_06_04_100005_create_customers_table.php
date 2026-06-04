<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 50)->unique();
            $table->string('customer_name', 255)->index();
            $table->string('status', 50)->default('active')->index();
            $table->string('kvk_number', 20)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->string('address_street', 150)->nullable();
            $table->string('address_number', 20)->nullable();
            $table->string('address_postal', 20)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_country', 100)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 190)->nullable();
            $table->string('external_id', 100)->nullable()->index();
            $table->string('source_system', 50)->nullable();
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('function', 150)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('customers');
    }
};
