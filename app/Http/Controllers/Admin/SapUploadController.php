<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use App\Services\Sap\SapImportService;
use App\Services\Sap\SapUserCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Beheer → Uploads SAP: per bestandssoort een kaart met de exacte bestandsnaam,
 * frequentie en laatste stand; upload via dezelfde kaart. Plus het koppelscherm
 * voor SAP-gebruikerscodes die niet automatisch aan een medewerker te koppelen zijn.
 */
class SapUploadController extends Controller
{
    public function __construct(private SapImportService $service) {}

    public function index()
    {
        $soorten = [];
        foreach ((array) config('sap.soorten') as $sleutel => $cfg) {
            $laatste = SapUpload::laatste($sleutel);
            $oud = $laatste ? $laatste->created_at->diffInDays(now()) > $cfg['frequentie_dagen'] + 1 : true;
            $soorten[$sleutel] = $cfg + ['laatste' => $laatste, 'verouderd' => $oud];
        }
        $historie = SapUpload::with('user')->orderByDesc('id')->limit(25)->get();
        $ongekoppeld = SapUserCode::whereNull('employee_id')->count();

        return view('admin.sap_uploads.index', compact('soorten', 'historie', 'ongekoppeld'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:'.(config('boels.import.max_file_size_mb', 25) * 1024)],
            'soort' => ['nullable', 'string', 'in:'.implode(',', array_keys((array) config('sap.soorten')))],
        ]);
        $bestand = $request->file('file');
        $pad = $bestand->store(config('boels.import.storage_path', 'imports').'/sap');
        try {
            $res = $this->service->importeer(Storage::path($pad), $bestand->getClientOriginalName(), auth()->id(), $request->input('soort') ?: null);
        } catch (\Throwable $e) {
            report($e);
            $res = ['ok' => false, 'melding' => 'Verwerking mislukt: '.$e->getMessage()];
        } finally {
            Storage::delete($pad); // persoonsgegevens van klanten niet op schijf laten staan
        }

        return redirect()->route('admin.sap-uploads.index')->with($res['ok'] ? 'import_result' : 'import_error', $res['melding']);
    }

    public function codes(SapUserCodes $codes)
    {
        $rijen = SapUserCode::with('employee')->orderByRaw('CASE WHEN employee_id IS NULL THEN 0 ELSE 1 END')->orderByDesc('gezien')->get()
            ->map(function (SapUserCode $c) use ($codes) {
                $c->kandidaten = $c->employee_id ? collect() : $codes->kandidaten($c->code);

                return $c;
            });
        $medewerkers = Employee::query()->where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.sap_uploads.codes', compact('rijen', 'medewerkers'));
    }

    public function koppel(Request $request, SapUserCode $code)
    {
        $data = $request->validate(['employee_id' => ['nullable', 'integer', 'exists:employees,id']]);
        $code->update(['employee_id' => $data['employee_id'] ?: null, 'bron' => $data['employee_id'] ? 'handmatig' : 'onbekend']);
        // Al geïmporteerde planning-orders met deze code/naam bijwerken
        if ($data['employee_id']) {
            \App\Models\PlanningOrder::whereNull('employee_id')->where(fn ($q) => $q->where('sales_person', $code->weergave)->orWhere('sales_person', $code->code))->update(['employee_id' => $data['employee_id']]);
        }

        return redirect()->route('admin.sap-uploads.codes')->with('ok', 'Koppeling opgeslagen voor '.$code->weergave.'.');
    }
}
