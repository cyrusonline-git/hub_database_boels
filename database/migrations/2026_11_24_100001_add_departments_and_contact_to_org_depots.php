<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Afdelingen + contactgegevens per vestiging (Wim 24-11-2026).
 *
 * CORE is leidend voor welke afdelingen een vestiging heeft (binnendienst, expeditie, werkplaats, servicedesk,
 * fleet, projecten — vaste lijst in config/boels.php → afdelingen) en voor adres/telefoon. Child-apps lezen dit via
 * /api/infrastructure en /api/internal/infrastructure. De schade-app gebruikt het om bij vestigingen zonder
 * servicedesk de werkplaats de servicedesk-taken te laten doen.
 *
 * Alleen toevoegen (idempotent): bestaande kolommen en rijen blijven onaangeroerd. Standaard krijgt een depot
 * binnendienst + expeditie + werkplaats; Rotterdam-Europoort (759) ook servicedesk, fleet en projecten.
 * Alleen depots waarvan departments nog leeg is worden gevuld — handmatige keuzes worden nooit overschreven.
 */
return new class extends Migration
{
    public function up(): void
    {
        $kolommen = [
            'departments' => fn (Blueprint $t) => $t->text('departments')->nullable(),
            'street' => fn (Blueprint $t) => $t->string('street', 150)->nullable(),
            'postal_code' => fn (Blueprint $t) => $t->string('postal_code', 20)->nullable(),
            'phone' => fn (Blueprint $t) => $t->string('phone', 40)->nullable(),
            'country' => fn (Blueprint $t) => $t->string('country', 2)->nullable(),
        ];
        foreach ($kolommen as $naam => $maak) {
            if (! Schema::hasColumn('org_depots', $naam)) {
                Schema::table('org_depots', fn (Blueprint $t) => $maak($t));
            }
        }

        foreach (DB::table('org_depots')->whereNull('departments')->get(['id', 'name', 'number']) as $d) {
            $nummers = array_map('trim', explode(',', (string) $d->number));
            $isRotterdam = in_array('759', $nummers, true) || $d->name === 'Rotterdam-Europoort';
            $afd = $isRotterdam
                ? ['binnendienst', 'expeditie', 'werkplaats', 'servicedesk', 'fleet', 'projecten']
                : ['binnendienst', 'expeditie', 'werkplaats'];
            DB::table('org_depots')->where('id', $d->id)->update(['departments' => json_encode($afd)]);
        }
    }

    public function down(): void
    {
        // Bewust leeg: nooit gegevens weggooien bij een rollback (de kolommen zijn nullable en storen niemand).
    }
};
