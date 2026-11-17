<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vlootvoorraad Industrial (Smartview "Items With Every Status"): momentopname van
 * alle machines met status en huidig depot. Meerdere uploads per dag; de regels van
 * de laatste uploads blijven staan (config sap.soorten.vloot.bewaar_snapshots), de
 * samenvatting per upload in sap_uploads blijft altijd (trend).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('item_number', 30);
            $t->string('item_description')->nullable();
            $t->string('group_code', 20)->nullable();
            $t->string('status', 40)->nullable();          // zoals in Smartview
            $t->string('status_key', 20);                   // beschikbaar | verhuurd | onderhoud | reparatie | onderweg | overig
            $t->string('depot', 10)->nullable();            // CURRDEPOT (depotnummer)
            $t->string('location', 30)->nullable();         // stellingplaats
            $t->index(['upload_id', 'item_number']);
            $t->index(['upload_id', 'group_code', 'status_key']);
            $t->index(['upload_id', 'depot', 'status_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_items');
    }
};
