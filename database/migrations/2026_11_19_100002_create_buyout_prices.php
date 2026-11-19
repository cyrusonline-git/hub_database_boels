<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Afkoopprijzen per subgroep (lijst "Afkoopprijzen industrial", via de Import Engine). Gebruikt door o.a. de
 * schade-app om bij een schade het afkooptarief te tonen (economisch total loss of repareren?). Wim 19-11-2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyout_prices', function (Blueprint $t) {
            $t->id();
            $t->string('subgroup', 30)->unique();
            $t->string('type', 20)->nullable();             // Uniek | Bulk
            $t->string('description')->nullable();
            $t->decimal('price', 12, 2)->nullable();          // NULL = op aanvraag / onbekend
            $t->boolean('on_request')->default(false);       // "op aanvraag"
            $t->string('price_raw', 50)->nullable();
            $t->string('source_system', 50)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        // Kolomkoppen van de lijst alvast koppelen in de Import Engine
        if (Schema::hasTable('field_aliases')) {
            foreach (['Type' => 'type', 'Subgroup' => 'subgroup', 'Subgroep' => 'subgroup', 'Subgroup + description' => 'description',
                'Omschrijving' => 'description', 'Afkoopprijs definitief' => 'price', 'Afkoopprijs' => 'price'] as $alias => $veld) {
                DB::table('field_aliases')->updateOrInsert(['entity' => 'buyout_price', 'alias' => $alias], ['field' => $veld, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buyout_prices');
        if (Schema::hasTable('field_aliases')) {
            DB::table('field_aliases')->where('entity', 'buyout_price')->delete();
        }
    }
};
