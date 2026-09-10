<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Backend van de Go-Assets aanvraagtool (resources/views/tools/go-assets.blade.php).
 *
 * De tool praat via ?action=… (JSON) — dezelfde contractvorm als de
 * losse api.php waarmee de tool geleverd is, maar dan binnen CORE:
 * ingelogde gebruiker = aanvrager, data in de CORE-database, CSRF via
 * de X-CSRF-TOKEN-header die de pagina meegeeft.
 */
class GoAssetsController extends Controller
{
    public function api(Request $request)
    {
        $user = $request->user();
        $gebruiker = ['naam' => $user->name, 'email' => $user->email];
        $actie = (string) $request->query('action', '');
        $in = $request->isMethod('post') ? (array) $request->json()->all() : [];

        switch ($actie) {
            case 'me':
                return ['ok' => true, 'gebruiker' => $gebruiker];

            case 'list':
                $this->autoStart();
                return ['ok' => true, 'projecten' => $this->projecten(), 'log' => $this->log()];

            case 'create':
                return $this->create($in, $gebruiker);

            case 'status':
                return $this->status($in, $gebruiker);

            case 'log':
                return $this->schrijfLog($in, $gebruiker);

            case 'klanten':
                return $this->klanten((string) $request->query('q', ''));

            case 'opruim_test':
                return $this->opruimTest($gebruiker);

            case 'mail':
                return $this->verstuurMail($in, $user);

            case 'mail_outlook':
                return $this->registreerOutlookMail($in, $user);

            case 'mails':
                return $this->mails((string) $request->query('project_id', ''), (string) $request->query('ref', ''));
        }

        return response()->json(['ok' => false, 'fout' => 'Onbekende actie'], 400);
    }

    // ------------------------------------------------------------ lezen

    /**
     * Projecten waarvan de startdatum is bereikt automatisch op Actief
     * zetten — ook als de omgeving nog niet als ontvangen/getest is
     * gemarkeerd. Draait bij elke lijst-aanroep; idempotent.
     */
    private function autoStart(): void
    {
        $vandaag = now()->toDateString();
        $rijen = DB::table('go_assets_projecten')
            ->whereIn('status', ['aangevraagd', 'omgeving_gereed', 'getest'])
            ->get();

        foreach ($rijen as $rij) {
            $data = json_decode($rij->data, true) ?: [];
            $start = substr((string) ($data['startdatum'] ?? ''), 0, 10);
            if ($start === '' || $start > $vandaag) {
                continue;
            }
            $data['status'] = 'actief';
            $data['gestart_op'] = now()->toIso8601String();
            $data['gestart_automatisch'] = true;
            $data['gewijzigd_op'] = now()->toIso8601String();
            DB::table('go_assets_projecten')->where('id', $rij->id)->update([
                'status' => 'actief',
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
            DB::table('go_assets_log')->insert([
                'id' => 'L' . now()->format('YmdHis') . substr(md5(uniqid('', true)), 0, 4),
                'project_id' => $rij->id, 'ref' => $rij->ref,
                'actie' => 'Status → Actief (automatisch)',
                'details' => 'Startdatum ' . $start . ' bereikt — project automatisch gestart'
                    . ($rij->status !== 'getest' ? ' (vorige status: ' . $rij->status . ')' : ''),
                'door' => 'CORE', 'door_email' => '',
                'op' => now(), 'created_at' => now(),
            ]);
        }
    }

    private function projecten(): array
    {
        return DB::table('go_assets_projecten')->orderByDesc('created_at')->get()
            ->map(fn ($r) => $this->projectUit($r))->values()->all();
    }

    private function projectUit(object $rij): array
    {
        $data = json_decode($rij->data, true) ?: [];
        // Kolommen zijn leidend boven de JSON-kopie
        $data['id'] = $rij->id;
        $data['ref'] = $rij->ref;
        $data['status'] = $rij->status;
        $data['aanvrager_naam'] = $rij->aanvrager_naam;
        $data['aanvrager_email'] = $rij->aanvrager_email;
        $data['test'] = ! empty($rij->is_test);
        return $data;
    }

    private function log(): array
    {
        return DB::table('go_assets_log')->orderByDesc('op')->orderByDesc('created_at')->limit(1000)->get()
            ->map(fn ($r) => [
                'id' => $r->id, 'project_id' => $r->project_id, 'ref' => $r->ref,
                'actie' => $r->actie, 'details' => $r->details,
                'door' => $r->door, 'door_email' => $r->door_email,
                'op' => $this->iso($r->op),
            ])->values()->all();
    }

    // ------------------------------------------------------------ schrijven

    private function create(array $p, array $gebruiker)
    {
        if (empty($p) || ! is_array($p['klant'] ?? null)) {
            return response()->json(['ok' => false, 'fout' => 'Geen aanvraaggegevens ontvangen'], 422);
        }

        $id = $this->tekst($p['id'] ?? '', 40) ?: ('P' . now()->format('YmdHis') . substr(md5(uniqid('', true)), 0, 4));
        // Testmodus: eigen reeks TEST-JJJJ-NNN, de echte GA-reeks blijft ongemoeid
        $isTest = ! empty($p['test']);
        // Referentie server-side bepalen (per jaar oplopend) — voorkomt
        // dubbele nummers als twee collega's tegelijk aanvragen.
        $ref = $this->volgendeRef($isTest ? 'TEST' : 'GA');

        // Aanvrager = ingelogde CORE-gebruiker, altijd
        $p['id'] = $id;
        $p['ref'] = $ref;
        $p['status'] = 'aangevraagd';
        $p['test'] = $isTest;
        $p['aanvrager_naam'] = $gebruiker['naam'];
        $p['aanvrager_email'] = $gebruiker['email'];
        $p['aangemaakt_op'] = $p['aangemaakt_op'] ?? now()->toIso8601String();

        DB::table('go_assets_projecten')->insert([
            'id' => $id,
            'ref' => $ref,
            'status' => 'aangevraagd',
            'is_test' => $isTest,
            'aanvrager_naam' => $gebruiker['naam'],
            'aanvrager_email' => $gebruiker['email'],
            'contractnummer' => $this->tekst($p['contractnummer'] ?? '', 60),
            'plaats' => $this->tekst($p['plaats'] ?? '', 120),
            'data' => json_encode($p, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'project' => $p];
    }

    private function status(array $in, array $gebruiker)
    {
        $id = $this->tekst($in['id'] ?? '', 40);
        $status = $this->tekst($in['status'] ?? '', 30);
        $rij = $id !== '' ? DB::table('go_assets_projecten')->where('id', $id)->first() : null;
        if (! $rij || $status === '') {
            return response()->json(['ok' => false, 'fout' => 'Project niet gevonden'], 404);
        }

        $data = json_decode($rij->data, true) ?: [];
        $data['status'] = $status;
        $data['gewijzigd_op'] = now()->toIso8601String();
        $data['gewijzigd_door'] = $gebruiker['naam'];
        if (! empty($in['afmeld_reden'])) {
            $data['afmeld_reden'] = $this->tekst($in['afmeld_reden'], 2000);
        }
        if ($status === 'afgemeld') {
            $data['afgemeld_op'] = now()->toIso8601String();
        }
        // Eventuele extra velden van de tool meenemen (behalve de vaste)
        foreach ($in as $k => $v) {
            if (! in_array($k, ['id', 'status', 'door', 'door_email', 'afmeld_reden'], true) && is_scalar($v)) {
                $data[$k] = $v;
            }
        }

        DB::table('go_assets_projecten')->where('id', $id)->update([
            'status' => $status,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'project' => $this->projectUit(DB::table('go_assets_projecten')->where('id', $id)->first())];
    }

    private function schrijfLog(array $in, array $gebruiker)
    {
        $actie = $this->tekst($in['actie'] ?? '', 100);
        if ($actie === '') {
            return response()->json(['ok' => false, 'fout' => 'Geen actie'], 422);
        }
        $id = $this->tekst($in['id'] ?? '', 40) ?: ('L' . now()->format('YmdHis') . substr(md5(uniqid('', true)), 0, 4));

        DB::table('go_assets_log')->updateOrInsert(['id' => $id], [
            'project_id' => $this->tekst($in['project_id'] ?? '', 40) ?: null,
            'ref' => $this->tekst($in['ref'] ?? '', 20) ?: null,
            'actie' => $actie,
            'details' => $this->tekst($in['details'] ?? '', 4000),
            'door' => $gebruiker['naam'],
            'door_email' => $gebruiker['email'],
            'op' => now(),
            'created_at' => now(),
        ]);

        return ['ok' => true];
    }

    /** Klant zoeken in het CORE-klantenbestand op naam, concern of klantnummer. */
    private function klanten(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['ok' => true, 'klanten' => []];
        }
        $rijen = \App\Models\Customer::query()
            ->where(fn ($w) => $w->where('customer_number', 'like', "%$q%")
                ->orWhere('customer_name', 'like', "%$q%")
                ->orWhere('concern_name', 'like', "%$q%"))
            ->orderByRaw("CASE WHEN customer_number = ? THEN 0 ELSE 1 END", [$q])
            ->orderBy('customer_name')
            ->limit(15)
            ->get();

        return ['ok' => true, 'klanten' => $rijen->map(fn ($c) => [
            'nummer' => $c->customer_number,
            'naam' => $c->customer_name,
            'concern' => $c->concern_name,
            'btw' => $c->vat_number,
            'adres' => trim(($c->address_street ?? '') . ' ' . ($c->address_number ?? '')),
            'postcode' => $c->address_postal,
            'plaats' => $c->address_city,
            'land' => $c->address_country,
            'email' => $c->email,
            'telefoon' => $c->phone,
        ])->values()->all()];
    }

    // ------------------------------------------------------------ helpers

    private function volgendeRef(string $prefix = 'GA'): string
    {
        $jaar = now()->format('Y');
        $laatste = DB::table('go_assets_projecten')
            ->where('ref', 'like', "$prefix-$jaar-%")
            ->orderByDesc('ref')->value('ref');
        $n = $laatste ? intval(substr($laatste, -3)) + 1 : 1;
        return sprintf('%s-%s-%03d', $prefix, $jaar, $n);
    }

    /**
     * Mail versturen via de CORE-mailserver (mailto-links zijn voor deze
     * mails te lang voor Windows/Outlook). Alleen naar de vaste adressen
     * van de tool; afzender = CORE, reply-to en cc = de aanvrager.
     * Testmodus: alleen naar de aanvrager zelf.
     */
    private function verstuurMail(array $in, $user)
    {
        $aan = strtolower($this->tekst($in['aan'] ?? '', 190));
        $onderwerp = $this->tekst($in['onderwerp'] ?? '', 200);
        $tekst = trim((string) ($in['tekst'] ?? ''));
        $test = ! empty($in['test']);
        $toegestaan = ['sitesecurity@boels.com', 'support@iq-pass.com'];

        if ($onderwerp === '' || $tekst === '' || ! in_array($aan, $toegestaan, true)) {
            return response()->json(['ok' => false, 'fout' => 'Ongeldige mailgegevens'], 422);
        }
        if (mb_strlen($tekst) > 20000) {
            return response()->json(['ok' => false, 'fout' => 'Mailtekst te lang'], 422);
        }

        $naar = $test ? $user->email : $aan;
        try {
            $html = $this->naarHtml($tekst);
            \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($naar, $onderwerp, $user, $test, $tekst) {
                // Afzenderadres blijft het CORE-adres (SPF/DKIM), maar de naam is
                // die van de aanvrager en "Beantwoorden" gaat naar de aanvrager.
                $vanAdres = config('mail.from.address') ?: 'noreply@sorai.nl';
                $m->from($vanAdres, $user->name . ' via Boels CORE')
                  ->to($naar)->subject(($test ? '[TEST] ' : '') . $onderwerp)
                  ->replyTo($user->email, $user->name)
                  ->text($tekst); // platte-tekstversie als alternatief deel
                if (! $test) {
                    $m->cc($user->email, $user->name);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'fout' => 'Mailserver gaf een fout — probeer Outlook (tekst op klembord)'], 500);
        }

        $this->bewaarMail($in, $user, $naar, $onderwerp, $tekst, 'core', $test);
        DB::table('go_assets_log')->insert([
            'id' => 'L' . now()->format('YmdHis') . substr(md5(uniqid('', true)), 0, 4),
            'project_id' => $this->tekst($in['project_id'] ?? '', 40) ?: null,
            'ref' => $this->tekst($in['ref'] ?? '', 20) ?: null,
            'actie' => $test ? 'Testmail verstuurd (naar jezelf)' : 'Mail verstuurd via CORE',
            'details' => 'Aan ' . $naar . ' — ' . $onderwerp,
            'door' => $user->name, 'door_email' => $user->email,
            'op' => now(), 'created_at' => now(),
        ]);

        return ['ok' => true, 'naar' => $naar, 'test' => $test];
    }

    /**
     * Platte mailtekst van de tool omzetten naar nette HTML: koppen vet,
     * "Label: waarde"-regels als tabel (labels lijnen uit, ongeacht lettertype).
     */
    private function naarHtml(string $tekst): string
    {
        $regels = preg_split('/\r?\n/', $tekst);
        $uit = [];
        $inTabel = false;
        $sluitTabel = function () use (&$uit, &$inTabel) {
            if ($inTabel) { $uit[] = '</table>'; $inTabel = false; }
        };
        foreach ($regels as $r) {
            $rt = rtrim($r);
            if ($rt === '') { $sluitTabel(); $uit[] = '<div style="height:8px"></div>'; continue; }
            if (preg_match('/^=+$/', $rt)) { continue; }
            if (preg_match('/^(\s*)([^:]{2,45}?):\s(.*)$/u', $rt, $m) || preg_match('/^(\s*)([^:]{2,45}?):$/u', $rt, $m)) {
                $inspring = strlen($m[1]) > 0;
                $label = e(trim($m[2]));
                $waarde = e(trim($m[3] ?? ''));
                if (! $inTabel) { $uit[] = '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse">'; $inTabel = true; }
                $uit[] = '<tr><td style="padding:1px 14px 1px ' . ($inspring ? '18px' : '0') . ';color:#555;white-space:nowrap;vertical-align:top">' . $label . '</td>'
                       . '<td style="padding:1px 0;vertical-align:top"><b>' . ($waarde !== '' ? $waarde : '—') . '</b></td></tr>';
                continue;
            }
            $sluitTabel();
            if (preg_match('/^[A-Z0-9 \-\/()&]{4,}$/u', $rt) && $rt === mb_strtoupper($rt)) {
                $uit[] = '<div style="margin:14px 0 4px;font-weight:700;font-size:15px;color:#d25405;border-bottom:2px solid #d25405;padding-bottom:2px">' . e($rt) . '</div>';
            } else {
                $uit[] = '<div>' . e($rt) . '</div>';
            }
        }
        $sluitTabel();
        return '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;line-height:1.45;color:#111">' . implode("\n", $uit) . '</div>';
    }

    /** Mailkopie bewaren (verstuurd via CORE of geopend in Outlook). */
    private function bewaarMail(array $in, $user, string $naar, string $onderwerp, string $tekst, string $via, bool $test): void
    {
        DB::table('go_assets_mails')->insert([
            'project_id' => $this->tekst($in['project_id'] ?? '', 40) ?: null,
            'ref' => $this->tekst($in['ref'] ?? '', 20) ?: null,
            'aan' => $naar,
            'onderwerp' => $onderwerp,
            'tekst' => $tekst,
            'via' => $via,
            'is_test' => $test,
            'door' => $user->name,
            'door_email' => $user->email,
            'verzonden_op' => now(),
            'created_at' => now(),
        ]);
    }

    /** Mail die in Outlook is geopend registreren (tekst zoals in de tool stond). */
    private function registreerOutlookMail(array $in, $user)
    {
        $aan = strtolower($this->tekst($in['aan'] ?? '', 190));
        $onderwerp = $this->tekst($in['onderwerp'] ?? '', 200);
        $tekst = trim((string) ($in['tekst'] ?? ''));
        if ($aan === '' || $onderwerp === '' || $tekst === '') {
            return response()->json(['ok' => false, 'fout' => 'Ongeldige mailgegevens'], 422);
        }
        $test = ! empty($in['test']);
        $this->bewaarMail($in, $user, $aan, $onderwerp, mb_substr($tekst, 0, 20000), 'outlook', $test);
        DB::table('go_assets_log')->insert([
            'id' => 'L' . now()->format('YmdHis') . substr(md5(uniqid('', true)), 0, 4),
            'project_id' => $this->tekst($in['project_id'] ?? '', 40) ?: null,
            'ref' => $this->tekst($in['ref'] ?? '', 20) ?: null,
            'actie' => 'Mail geopend in Outlook',
            'details' => 'Aan ' . $aan . ' — ' . $onderwerp,
            'door' => $user->name, 'door_email' => $user->email,
            'op' => now(), 'created_at' => now(),
        ]);
        return ['ok' => true];
    }

    /** Bewaarde mails, per project of alle (nieuwste eerst). */
    private function mails(string $projectId, string $ref = ''): array
    {
        $q = DB::table('go_assets_mails')->orderByDesc('verzonden_op');
        if ($projectId !== '' || $ref !== '') {
            $q->where(function ($w) use ($projectId, $ref) {
                if ($projectId !== '') { $w->where('project_id', $projectId); }
                if ($ref !== '') { $w->orWhere('ref', $ref); }
            });
        }
        return ['ok' => true, 'mails' => $q->limit(200)->get()->map(fn ($m) => [
            'id' => $m->id, 'project_id' => $m->project_id, 'ref' => $m->ref,
            'aan' => $m->aan, 'onderwerp' => $m->onderwerp, 'tekst' => $m->tekst,
            'via' => $m->via, 'test' => (bool) $m->is_test,
            'door' => $m->door, 'op' => $this->iso($m->verzonden_op),
        ])->values()->all()];
    }

    /** Alle testaanvragen (is_test) plus hun logregels verwijderen. */
    private function opruimTest(array $gebruiker): array
    {
        $ids = DB::table('go_assets_projecten')->where('is_test', true)->pluck('id')->all();
        $aantal = count($ids);
        DB::transaction(function () use ($ids) {
            DB::table('go_assets_log')->where(fn ($w) => $w->whereIn('project_id', $ids)->orWhere('ref', 'like', 'TEST-%'))->delete();
            DB::table('go_assets_projecten')->where('is_test', true)->delete();
            DB::table('go_assets_mails')->where(fn ($w) => $w->where('is_test', true)->orWhereIn('project_id', $ids))->delete();
        });
        if ($aantal > 0) {
            DB::table('go_assets_log')->insert([
                'id' => 'L' . now()->format('YmdHis') . substr(md5(uniqid('', true)), 0, 4),
                'project_id' => null, 'ref' => null,
                'actie' => 'Testaanvragen opgeruimd',
                'details' => "$aantal testaanvra" . ($aantal === 1 ? 'ag' : 'gen') . ' met logregels verwijderd',
                'door' => $gebruiker['naam'], 'door_email' => $gebruiker['email'],
                'op' => now(), 'created_at' => now(),
            ]);
        }
        return ['ok' => true, 'verwijderd' => $aantal];
    }

    private function tekst($v, int $max): string
    {
        return mb_substr(trim((string) (is_scalar($v) ? $v : '')), 0, $max);
    }

    private function iso($dt): string
    {
        try {
            return \Carbon\Carbon::parse($dt)->toIso8601String();
        } catch (\Throwable $e) {
            return (string) $dt;
        }
    }
}
