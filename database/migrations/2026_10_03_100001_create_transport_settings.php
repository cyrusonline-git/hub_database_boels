<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transportcalculator — tarieven server-side.
 *
 * De transportcalculator (/tools/transport) bewaarde zijn tarieven in browser-
 * localStorage, per gebruiker/apparaat. Daardoor zag elke collega andere cijfers en
 * kon geen API de actuele tarieven ophalen. Deze tabel maakt de tariefdataset
 * centraal (één JSON-blob-row), zodat de tool én de transportplanner dezelfde,
 * actuele tarieven gebruiken. version = optimistic lock tegen gelijktijdig opslaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_settings', function (Blueprint $table) {
            $table->id();
            $table->longText('payload');          // de volledige app-state (zelfde JSON als voorheen in de view)
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Eenmalige seed: de huidige default-dataset uit de tool (database/data/transport_default.json).
        $pad = database_path('data/transport_default.json');
        $payload = is_file($pad) ? file_get_contents($pad) : '{}';
        // Alleen seeden als het geldige JSON is; anders een leeg object (de tool valt dan terug op zijn eigen default).
        if (json_decode($payload) === null) {
            $payload = '{}';
        }

        if (DB::table('transport_settings')->count() === 0) {
            DB::table('transport_settings')->insert([
                'id' => 1,
                'payload' => $payload,
                'version' => 1,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_settings');
    }
};
