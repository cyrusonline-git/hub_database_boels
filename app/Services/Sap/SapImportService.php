<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\InvoiceRunLine;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use Illuminate\Support\Facades\DB;

/**
 * Orkestratie: bestand lezen → soort herkennen → importer → sap_uploads-rij →
 * oude snapshots opruimen. Geeft een array terug voor de melding in het scherm.
 */
class SapImportService
{
    private const IMPORTERS = [
        'wow' => WowImporter::class,
        'factuurrun' => FactuurrunImporter::class,
        'planning' => PlanningImporter::class,
        'reserveringen' => ReserveringenImporter::class,
    ];

    private const REGELS = [
        'wow' => HireLine::class,
        'factuurrun' => InvoiceRunLine::class,
        'planning' => PlanningOrder::class,
        'reserveringen' => ReservationLine::class,
    ];

    /**
     * @param  string  $pad  absoluut pad naar het bestand
     * @param  string|null  $verwacht  soort-sleutel waarop de gebruiker het bestand neerzette (optioneel)
     * @return array{ok: bool, soort?: string, upload?: SapUpload, melding: string, meldingen?: array}
     */
    public function importeer(string $pad, string $bestandsnaam, ?int $userId = null, ?string $verwacht = null): array
    {
        $hash = hash_file('sha256', $pad);
        $rijen = SapBestand::rijen($pad);
        $herkend = Herkenning::herken($rijen);
        if (! $herkend['soort']) {
            return ['ok' => false, 'melding' => $herkend['reden'] ?? 'Bestand niet herkend.'];
        }
        $soort = $herkend['soort'];
        $label = config("sap.soorten.$soort.label", $soort);
        if ($verwacht && $verwacht !== $soort) {
            return ['ok' => false, 'melding' => "Dit bestand is herkend als \"$label\", maar je zette het bij \"".config("sap.soorten.$verwacht.label", $verwacht).'". Upload het op de juiste kaart.'];
        }
        $dubbel = SapUpload::where('soort', $soort)->where('hash', $hash)->orderByDesc('id')->first();
        if ($dubbel) {
            return ['ok' => false, 'soort' => $soort, 'melding' => "Dit exacte bestand is al verwerkt op {$dubbel->created_at->format('d-m-Y H:i')} (upload #{$dubbel->id}). Niets gewijzigd."];
        }

        $importer = app(self::IMPORTERS[$soort]);
        $upload = null;
        $res = DB::transaction(function () use ($importer, $rijen, $herkend, $soort, $bestandsnaam, $userId, $hash, &$upload) {
            $upload = SapUpload::create(['soort' => $soort, 'bestandsnaam' => $bestandsnaam, 'user_id' => $userId, 'hash' => $hash]);
            /** @var ImportResultaat $res */
            $res = $importer->importeer($rijen, $upload->id, $herkend['koprij'], $herkend['kolommen']);
            $upload->update([
                'rapportdatum' => $res->rapportdatum,
                'regels' => $res->regels,
                'overgeslagen' => $res->overgeslagen,
                'contracten' => $res->contracten,
                'samenvatting' => $res->samenvatting,
            ]);

            return $res;
        });
        $this->ruimOp($soort);
        if (class_exists(\App\Models\AuditLog::class)) {
            try {
                \App\Models\AuditLog::create(['user_id' => $userId, 'event' => 'sap_upload', 'auditable_type' => SapUpload::class, 'auditable_id' => $upload->id, 'new_values' => ['soort' => $soort, 'bestand' => $bestandsnaam, 'regels' => $res->regels]]);
            } catch (\Throwable) {
            }
        }

        return [
            'ok' => true,
            'soort' => $soort,
            'upload' => $upload->fresh(),
            'melding' => "$label verwerkt: {$res->regels} regels, {$res->contracten} contracten/orders, {$res->overgeslagen} overgeslagen (groepregels, lege regels of ander depot).",
            'meldingen' => $res->meldingen,
        ];
    }

    /** Regels van oude snapshots verwijderen; de upload-rij met samenvatting blijft. */
    public function ruimOp(string $soort): void
    {
        $bewaar = max(1, (int) config('sap.bewaar_snapshots', 30));
        $houden = SapUpload::where('soort', $soort)->orderByDesc('id')->limit($bewaar)->pluck('id');
        $oud = SapUpload::where('soort', $soort)->where('regels_opgeruimd', false)->whereNotIn('id', $houden)->pluck('id');
        if ($oud->isEmpty()) {
            return;
        }
        (self::REGELS[$soort])::whereIn('upload_id', $oud)->delete();
        SapUpload::whereIn('id', $oud)->update(['regels_opgeruimd' => true]);
    }
}
