<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PO-bewaking: logboek van PO-verzoeken per contract — wie is wanneer door wie om een referentie gevraagd (Wim 13-11-2026). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reference_requests')) {
            return;
        }
        Schema::create('reference_requests', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number', 20)->index();
            $t->string('gevraagd_aan')->nullable();          // naam/functie bij de klant (uitvoerder, inkoper …)
            $t->text('opmerking')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->date('gevraagd_op');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_requests');
    }
};
