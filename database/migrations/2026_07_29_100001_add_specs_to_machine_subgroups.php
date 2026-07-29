<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_subgroups', function (Blueprint $table) {
            $table->string('tabblad', 150)->nullable()->after('description');
            $table->string('categorie', 150)->nullable()->after('tabblad');
            $table->string('toepassing', 255)->nullable()->after('categorie');
            $table->string('merk', 150)->nullable()->after('toepassing');
            $table->string('type', 150)->nullable()->after('merk');
            $table->json('highlights')->nullable()->after('type');
            $table->json('specifications')->nullable()->after('highlights');
            $table->json('accessoires')->nullable()->after('specifications');
            $table->json('verkoopartikelen')->nullable()->after('accessoires');
            $table->json('alternatieven')->nullable()->after('verkoopartikelen');
            $table->string('service_codes', 100)->nullable()->after('alternatieven');
        });
    }

    public function down(): void
    {
        Schema::table('machine_subgroups', function (Blueprint $table) {
            $table->dropColumn([
                'tabblad', 'categorie', 'toepassing', 'merk', 'type',
                'highlights', 'specifications', 'accessoires',
                'verkoopartikelen', 'alternatieven', 'service_codes',
            ]);
        });
    }
};
