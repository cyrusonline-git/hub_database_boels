<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Depotnummer(s) op de infrastructuur-depots, zoals ze in het ERP/de
 * materieellijst voorkomen ("759 - Industrial Rotterdam"). Meerdere nummers
 * gescheiden door een komma. Child-apps (o.a. de Voorraad tool) lezen dit via
 * GET /api/infrastructure. Bekende nummers worden direct ingevuld (17-09-2026).
 */
return new class extends Migration
{
    private array $bekend = [
        'Rotterdam-Europoort' => '759',
        'Oude-Tonge' => '767',
        'Appingedam' => '764',
        'Geleen' => '765',
        'Antwerpen' => '762',
        'Emmen' => '763',
        'Shell Pernis' => '774',
        'BP Rotterdam-Europoort' => '771',
        'Exxon Rotterdam' => '786',
        'Shell Moerdijk' => '773',
        'Geleen - Chemelot' => '384, 769',
        'Geleen - Chemelot - Safety Shop' => '1209',
        'Gunvor Rotterdam -Europoort' => '772',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('org_depots', 'number')) {
            Schema::table('org_depots', function (Blueprint $table) {
                $table->string('number', 60)->nullable()->after('name');
            });
        }
        foreach ($this->bekend as $naam => $nummer) {
            DB::table('org_depots')->where('name', $naam)->whereNull('number')->update(['number' => $nummer]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('org_depots', 'number')) {
            Schema::table('org_depots', fn (Blueprint $table) => $table->dropColumn('number'));
        }
    }
};
