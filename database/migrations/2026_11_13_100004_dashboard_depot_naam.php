<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Depotnaam in CORE is "Rotterdam-Europoort" (niet "Rotterdam"): beperking van de Dashboard-tegel daarop zetten. */
return new class extends Migration
{
    public function up(): void
    {
        $namen = DB::table('employees')->whereNotNull('depot')->where('depot', 'like', '%Rotterdam%')->distinct()->pluck('depot')->all();
        $namen = array_values(array_unique(array_merge(['Rotterdam', 'Rotterdam-Europoort'], $namen)));
        DB::table('applications')->where('slug', 'dashboard')->update(['restricted_to_depots' => json_encode($namen), 'updated_at' => now()]);
    }

    public function down(): void {}
};
