<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Smartview 0.05 "Goods in to be off hired": machines die de expeditie goederen-in heeft geboekt (retour, nog niet
 * afgemeld/gefactureerd), met klant en contract. Bron voor tankapp en schade-app: retour gekomen machines staan
 * niet meer in de WOW.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_in_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('contract_number', 20)->index();
            $t->string('customer_number', 30)->nullable();
            $t->string('customer_name')->nullable();
            $t->string('item_number', 30)->index();
            $t->string('item_description')->nullable();
            $t->decimal('qty', 10, 2)->default(1);
            $t->date('delivery_date')->nullable();
            $t->date('hire_start_date')->nullable();
            $t->date('hire_end_date')->nullable();
            $t->date('collection_date')->nullable();
            $t->date('goods_in_date')->nullable();
            $t->string('item_type', 30)->nullable();
            $t->string('delivery_name')->nullable();
            $t->string('street')->nullable();
            $t->string('city')->nullable();
            $t->string('ordered_by')->nullable();
            $t->string('customer_reference')->nullable();
            $t->string('created_by_code', 20)->nullable();
            $t->index(['upload_id', 'item_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_in_lines');
    }
};
