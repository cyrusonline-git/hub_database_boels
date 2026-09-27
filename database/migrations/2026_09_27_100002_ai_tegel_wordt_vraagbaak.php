<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** De oude AI-tegel (dode link naar ai.sorai.nl) wordt de Boels Vraagbaak: eigen AI, intern, geen externe diensten. */
return new class extends Migration
{
    public function up(): void
    {
        $velden = [
            'name' => 'Boels Vraagbaak',
            'description' => 'Eigen AI: stel een vraag over de data van onze apps. Draait op onze eigen server, er gaat niets naar externe AI-diensten.',
            'url' => '/tools/vraagbaak',
            'icon' => 'bi-chat-square-text',
            'color' => '#FF6600',
            'active' => true,
            'updated_at' => now(),
        ];
        if (DB::table('applications')->where('slug', 'ai')->exists()) {
            DB::table('applications')->where('slug', 'ai')->update($velden + ['deleted_at' => null]);
        } else {
            DB::table('applications')->insert($velden + ['slug' => 'ai', 'sort_order' => 50, 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('applications')->where('slug', 'ai')->update(['name' => 'AI Assistant', 'url' => 'https://ai.sorai.nl', 'icon' => 'bi-robot']);
    }
};
