<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use App\Models\User;
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
        // Gebruikersaccounts zonder medewerkerkaart (Wim maakte vroeger mensen alleen als gebruiker aan):
        // die mag je ook koppelen; CORE maakt dan een medewerkerkaart en koppelt het account eraan.
        $kaartEmails = Employee::whereNotNull('email')->pluck('email')->map(fn ($e) => mb_strtolower($e))->flip();
        $gebruikers = User::whereNull('employee_id')->where('status', '!=', User::STATUS_DISABLED)->orderBy('name')->get(['id', 'name', 'email'])
            ->filter(fn ($u) => ! $u->email || ! $kaartEmails->has(mb_strtolower($u->email)))
            ->map(fn ($u) => ['id' => $u->id, 'naam' => $u->name, 'code' => SapUserCodes::codeVoorNaam($u->name)])->values();
        $rijen = SapUserCode::with('employee')->orderByRaw('CASE WHEN employee_id IS NULL THEN 0 ELSE 1 END')->orderByDesc('gezien')->get()
            ->map(function (SapUserCode $c) use ($codes, $gebruikers) {
                $c->kandidaten = $c->employee_id ? collect() : $codes->kandidaten($c->code);
                $prefix = str_starts_with($c->code, 'NAAM:') ? null : preg_replace('/\d+$/', '', $c->code);
                $c->gebruikerKandidaten = $c->employee_id ? collect() : $gebruikers->filter(fn ($u) => $prefix
                    ? $u['code'] === $prefix
                    : SapUserCodes::normaliseer($u['naam']) === substr($c->code, 5))->values();

                return $c;
            });
        $medewerkers = Employee::query()->where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.sap_uploads.codes', compact('rijen', 'medewerkers', 'gebruikers'));
    }

    public function koppel(Request $request, SapUserCode $code)
    {
        $data = $request->validate(['employee_id' => ['nullable', 'string', 'max:20']]);
        $keuze = (string) ($data['employee_id'] ?? '');
        $employeeId = null;
        if (str_starts_with($keuze, 'user:')) {
            // Gebruiker zonder medewerkerkaart → kaart aanmaken (zoals __telefoonlijst.php &maak=1) en account koppelen
            $u = User::findOrFail((int) substr($keuze, 5));
            $bestaand = $u->email ? Employee::whereRaw('LOWER(email) = ?', [mb_strtolower($u->email)])->first() : null;
            $e = $bestaand ?: Employee::create([
                'employee_number' => 'AUTO-'.strtoupper(substr(md5(mb_strtolower((string) ($u->email ?: $u->name))), 0, 8)),
                'name' => $u->name, 'email' => $u->email,
                'depot' => $u->allowed_depots[0] ?? null, 'area' => $u->allowed_areas[0] ?? null, 'country' => $u->allowed_countries[0] ?? null,
                'active' => true, 'source_system' => 'handmatig',
            ]);
            if (! $u->employee_id) {
                $u->employee_id = $e->id;
                $u->save();
            }
            $employeeId = $e->id;
        } elseif ($keuze !== '') {
            $employeeId = Employee::findOrFail((int) $keuze)->id;
        }
        $code->update(['employee_id' => $employeeId, 'bron' => $employeeId ? 'handmatig' : 'onbekend']);
        if ($employeeId) {
            \App\Models\PlanningOrder::whereNull('employee_id')->where(fn ($q) => $q->where('sales_person', $code->weergave)->orWhere('sales_person', $code->code))->update(['employee_id' => $employeeId]);
        }

        return redirect()->route('admin.sap-uploads.codes')->with('ok', 'Koppeling opgeslagen voor '.$code->weergave.(str_starts_with($keuze, 'user:') ? ' (medewerkerkaart aangemaakt en aan het account gekoppeld)' : '').'.');
    }

    /** Alle gebruikers zonder medewerkerkaart: kaart aanmaken + account koppelen + codes opnieuw matchen. */
    public function maakKaarten(SapUserCodes $codes)
    {
        $res = $codes->maakKaartenVoorGebruikers();
        $tekst = count($res['aangemaakt']).' medewerkerkaart(en) aangemaakt'.($res['aangemaakt'] ? ' ('.implode(', ', array_slice($res['aangemaakt'], 0, 15)).(count($res['aangemaakt']) > 15 ? ', …' : '').')' : '').'; '.$res['gekoppeld'].' SAP-code(s) alsnog automatisch gekoppeld.';

        return redirect()->route('admin.sap-uploads.codes')->with('ok', $tekst);
    }
}
