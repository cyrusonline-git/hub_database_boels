<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** De omschrijving onder de Transportplanner-tegel weghalen (de naam spreekt voor zich). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')->where('slug', 'planner')->update(['description' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'planner')->update([
            'description' => 'Transportritten plannen (bezorgen, ophalen, wisselen): planbord, chauffeursapp, klant-tracking, materieel en klanten uit CORE.',
            'updated_at' => now(),
        ]);
    }
};
