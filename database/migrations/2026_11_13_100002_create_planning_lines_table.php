<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Artikelregels per order uit de gegroepeerde 8.7-export (optionele aanvulling op 2.6). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('contract_number', 20)->index();
            $t->string('project_number', 30)->nullable();
            $t->string('customer_number', 30)->nullable();
            $t->string('customer_name')->nullable();
            $t->string('their_ref')->nullable();
            $t->string('contract_status', 40)->nullable();
            $t->string('item_status', 40)->nullable();
            $t->string('item_number', 30)->nullable();
            $t->string('item_description')->nullable();
            $t->decimal('qty', 10, 2)->default(1);
            $t->string('place_of_delivery')->nullable();
            $t->date('delivery_date')->nullable();
            $t->string('delivery_time', 10)->nullable();
            $t->string('created_by_code', 20)->nullable();
            $t->string('sales_person', 60)->nullable();
            $t->string('memo')->nullable();
            $t->index(['upload_id', 'contract_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_lines');
    }
};
