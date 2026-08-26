<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Livegang Projectschade: tegel-URL van de testversie (/v2) naar de
 * echte app. Tegel en launcher-vinkjes stonden al aan sinds de testfase.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')->where('slug', 'projectschade')->update([
            'url'        => 'https://projectschade.sorai.nl',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'projectschade')->update([
            'url' => 'https://projectschade.sorai.nl/v2',
        ]);
    }
};
