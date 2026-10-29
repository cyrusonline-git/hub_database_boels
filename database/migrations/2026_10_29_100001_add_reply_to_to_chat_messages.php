<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Reageren op een bericht (WhatsApp-stijl): verwijst naar het
            // geciteerde bericht. Wordt leeg als dat bericht wordt verwijderd.
            $table->foreignId('reply_to_id')->nullable()->after('image_path')
                ->constrained('chat_messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_id');
        });
    }
};
