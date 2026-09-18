<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Depot Antwerpen heeft in de reserveringenlijst ook nummer 9273 ("Industrial Antwerpen (BE)"). */
return new class extends Migration
{
    public function up(): void
    {
        $rij = DB::table('org_depots')->where('name', 'Antwerpen')->whereNull('deleted_at')->first();
        if ($rij) {
            $nummers = array_values(array_filter(array_map('trim', explode(',', (string) $rij->number))));
            if (! in_array('9273', $nummers, true)) {
                $nummers[] = '9273';
                DB::table('org_depots')->where('id', $rij->id)->update(['number' => implode(', ', $nummers)]);
            }
        }
    }

    public function down(): void
    {
        $rij = DB::table('org_depots')->where('name', 'Antwerpen')->first();
        if ($rij) {
            $nummers = array_values(array_filter(array_map('trim', explode(',', (string) $rij->number)), fn ($n) => $n !== '9273'));
            DB::table('org_depots')->where('id', $rij->id)->update(['number' => $nummers ? implode(', ', $nummers) : null]);
        }
    }
};
