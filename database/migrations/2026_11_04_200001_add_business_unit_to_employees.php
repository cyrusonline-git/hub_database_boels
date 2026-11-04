<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Business unit op de medewerkerkaart en op import-jobs.
 *
 * De Infrastructuur kent al business_units (BU → area → depot), maar de medewerker-
 * kaarten hingen daar nog niet aan: de Industrial-Excel was impliciet "alle
 * medewerkers" en een import in sync-modus zette alles wat niet in het bestand stond
 * inactief. Collega's van een andere BU (bijv. voor de Spoedverhuur-app) pasten daar
 * niet in.
 *  - employees.business_unit_id (nullable): bestaande kaarten → Industrial; de handmatig/
 *    telefoonlijst-kaarten blijven leeg zodat Wim ze per persoon toewijst (daar zitten
 *    zowel Industrial- als andere-BU-collega's in).
 *  - import_jobs.business_unit_id: per import de BU; de sync-modus blijft binnen die BU.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_units') || ! Schema::hasTable('employees')) {
            return;
        }
        $industrialId = DB::table('business_units')->whereNull('deleted_at')->where('name', 'Industrial')->value('id');
        if (! $industrialId) {
            $industrialId = DB::table('business_units')->insertGetId([
                'name' => 'Industrial', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('employees', 'business_unit_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('business_unit_id')->nullable()->after('employee_number')
                      ->constrained('business_units')->nullOnDelete();
            });
            DB::table('employees')
                ->whereNull('business_unit_id')
                ->where(fn ($w) => $w->whereNull('source_system')->orWhereNotIn('source_system', ['handmatig', 'telefoonlijst']))
                ->update(['business_unit_id' => $industrialId]);
        }

        if (Schema::hasTable('import_jobs') && ! Schema::hasColumn('import_jobs', 'business_unit_id')) {
            Schema::table('import_jobs', function (Blueprint $table) {
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('import_jobs', 'business_unit_id')) {
            Schema::table('import_jobs', fn (Blueprint $t) => $t->dropConstrainedForeignId('business_unit_id'));
        }
        if (Schema::hasColumn('employees', 'business_unit_id')) {
            Schema::table('employees', fn (Blueprint $t) => $t->dropConstrainedForeignId('business_unit_id'));
        }
    }
};
