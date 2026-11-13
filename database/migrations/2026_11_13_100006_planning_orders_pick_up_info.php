<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 8.7 kolom B (PickUpInfo): leeg = order staat niet op de scanner van de expeditie (Wim 13-11-2026). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('planning_orders', 'pick_up_info')) {
            Schema::table('planning_orders', fn (Blueprint $t) => $t->string('pick_up_info')->nullable()->after('del_memo'));
        }
    }

    public function down(): void
    {
        Schema::table('planning_orders', fn (Blueprint $t) => $t->dropColumn('pick_up_info'));
    }
};
