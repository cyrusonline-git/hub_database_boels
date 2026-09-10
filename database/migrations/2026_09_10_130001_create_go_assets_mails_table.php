<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Go-Assets aanvraagtool: elke mail die vanuit de tool is verstuurd (via de
 * CORE-mailserver) of in Outlook is geopend, wordt bewaard zodat je later
 * kunt terugkijken wat er precies naar Site Security / Support is gegaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('go_assets_mails')) {
            Schema::create('go_assets_mails', function (Blueprint $table) {
                $table->id();
                $table->string('project_id', 40)->nullable()->index();
                $table->string('ref', 20)->nullable()->index();
                $table->string('aan', 190);
                $table->string('onderwerp', 255);
                $table->longText('tekst');
                $table->string('via', 20)->default('core');   // core | outlook
                $table->boolean('is_test')->default(false);
                $table->string('door', 150)->nullable();
                $table->string('door_email', 190)->nullable();
                $table->dateTime('verzonden_op');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('go_assets_mails');
    }
};
