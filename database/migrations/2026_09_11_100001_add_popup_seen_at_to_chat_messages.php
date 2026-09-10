<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat-pop-up (config boels.chat_popup): bijhouden welke berichten al als
 * pop-up zijn getoond en weggeklikt, los van read_at (gelezen in de chat).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_messages', 'popup_seen_at')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->timestamp('popup_seen_at')->nullable()->after('read_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chat_messages', 'popup_seen_at')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn('popup_seen_at');
            });
        }
    }
};
