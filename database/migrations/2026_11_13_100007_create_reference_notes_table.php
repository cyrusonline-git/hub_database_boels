<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PO-bewaking: contracten/orders zonder klantreferentie of PO-nummer blijven gevolgd tot de referentie in Smartview staat (Wim 13-11-2026). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reference_notes')) {
            return;
        }
        Schema::create('reference_notes', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number', 20)->unique();
            $t->string('customer_name')->nullable();
            $t->string('status', 20)->default('open');     // open | opgevraagd | afgehandeld
            $t->text('opmerking')->nullable();
            $t->date('opgevraagd_op')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->date('eerst_gezien')->nullable();
            $t->date('laatst_gezien')->nullable();
            $t->date('verdwenen_op')->nullable();           // referentie staat er (of contract is weg) → automatisch opgelost
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_notes');
    }
};
