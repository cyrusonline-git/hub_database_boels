<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 3.4 "Contract Items due for delivery": leverdatum per regel + markering bijhuur (contractstatus Inhuur). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_lines', function (Blueprint $t) {
            $t->date('item_delivery_date')->nullable()->after('delivery_date')->index();
            $t->boolean('is_bijhuur')->default(false)->after('is_contract');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_lines', function (Blueprint $t) {
            $t->dropColumn(['item_delivery_date', 'is_bijhuur']);
        });
    }
};
