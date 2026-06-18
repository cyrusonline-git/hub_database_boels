<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('allowed_areas')->nullable()->after('active');
            $table->json('allowed_depots')->nullable()->after('allowed_areas');
            $table->json('allowed_countries')->nullable()->after('allowed_depots');
            $table->string('status', 30)->default('active')->after('allowed_countries');
            $table->string('activation_token', 80)->nullable()->after('status');
            $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token');
        });

        // Bestaande users zonder password (vroeg-aangemaakt) krijgen pending_activation,
        // de rest blijft 'active' (defaults).
        \DB::table('users')->whereNull('password')->orWhere('password', '')->update(['status' => 'pending_activation']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_areas', 'allowed_depots', 'allowed_countries',
                'status', 'activation_token', 'activation_token_expires_at',
            ]);
        });
    }
};
