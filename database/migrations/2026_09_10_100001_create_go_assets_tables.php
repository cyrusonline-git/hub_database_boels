<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Go-Assets aanvraagtool (Boels Industrial) als CORE-tool.
 *
 * - go_assets_projecten: elke aanvraag (ref GA-JJJJ-NNN) met status; het
 *   volledige aanvraagobject van de tool staat als JSON in `data`, de
 *   zoek-/sorteervelden apart.
 * - go_assets_log: elke actie (aanvraag, statuswijziging, afmelding,
 *   supportmelding) met wie/wanneer.
 * - Handige link op het dashboard onder de kop "Aanvraagtools".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('go_assets_projecten')) {
            Schema::create('go_assets_projecten', function (Blueprint $table) {
                $table->string('id', 40)->primary();          // door de tool gegenereerd (P…)
                $table->string('ref', 20)->unique();           // GA-2026-001
                $table->string('status', 30)->default('aangevraagd')->index();
                $table->string('aanvrager_naam', 150)->nullable();
                $table->string('aanvrager_email', 190)->nullable();
                $table->string('contractnummer', 60)->nullable();
                $table->string('plaats', 120)->nullable();
                $table->longText('data');                      // volledige aanvraag (JSON)
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('go_assets_log')) {
            Schema::create('go_assets_log', function (Blueprint $table) {
                $table->string('id', 40)->primary();          // door de tool gegenereerd (L…)
                $table->string('project_id', 40)->nullable()->index();
                $table->string('ref', 20)->nullable()->index();
                $table->string('actie', 100);
                $table->text('details')->nullable();
                $table->string('door', 150)->nullable();
                $table->string('door_email', 190)->nullable();
                $table->dateTime('op');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! DB::table('quick_links')->where('url', '/tools/go-assets')->exists()) {
            DB::table('quick_links')->insert([
                'title'       => 'Go-Assets aanvraagtool',
                'url'         => '/tools/go-assets',
                'icon'        => 'bi-laptop',
                'category'    => 'Aanvraagtools',
                'description' => 'Go-Assets omgeving en hardware aanvragen, testen, afmelden en loggen',
                'sort_order'  => 1,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('quick_links')->where('url', '/tools/go-assets')->delete();
        Schema::dropIfExists('go_assets_log');
        Schema::dropIfExists('go_assets_projecten');
    }
};
