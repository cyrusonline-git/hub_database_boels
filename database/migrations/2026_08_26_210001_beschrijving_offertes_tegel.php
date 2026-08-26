<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tegeltekst voor Boels Offertes — was leeg, waardoor de tegel als enige
 * geen omschrijving toonde. Alleen invullen als hij nog leeg is, zodat
 * een later handmatig aangepaste tekst (Beheer → Applicaties) blijft staan.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->where('slug', 'offertes')
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', ''))
            ->update([
                'description' => 'Aanvragen en verwerken van offertes Industrial',
                'updated_at'  => now(),
            ]);
    }

    public function down(): void
    {
        // Tekst laten staan — onschuldig en handmatig aanpasbaar in Beheer.
    }
};
