<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Gedeelde sleutel voor het ophalen van de gebruikerslijst
            // (core-users.php) van de app — rollenlijst blijft publiek.
            $table->string('sync_key', 64)->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('sync_key');
        });
    }
};
