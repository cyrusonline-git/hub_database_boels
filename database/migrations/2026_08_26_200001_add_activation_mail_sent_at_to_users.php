<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bijhouden wanneer de laatste activatie-/inlog-mail is verstuurd, zodat
 * de beheerder ziet wie al gemaild is (en dubbele mails kan vermijden).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('activation_mail_sent_at')->nullable()->after('activation_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activation_mail_sent_at');
        });
    }
};
