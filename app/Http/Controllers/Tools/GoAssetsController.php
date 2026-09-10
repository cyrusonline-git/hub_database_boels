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
                return ['ok' => true, 'projecten' => $this->projecten(), 'log' => $this->log()];

            case 'create':
                return $this->create($in, $gebruiker);

            case 'status':
                return $this->status($in, $gebruiker);

            case 'log':
                return $this->schrijfLog($in, $gebruiker);
        }

        return response()->json(['ok' => false, 'fout' => 'Onbekende actie'], 400);
    }

    // ------------------------------------------------------------ lezen

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
        // Referentie server-side bepalen (per jaar oplopend) — voorkomt
        // dubbele nummers als twee collega's tegelijk aanvragen.
        $ref = $this->volgendeRef();

        // Aanvrager = ingelogde CORE-gebruiker, altijd
        $p['id'] = $id;
        $p['ref'] = $ref;
        $p['status'] = 'aangevraagd';
        $p['aanvrager_naam'] = $gebruiker['naam'];
        $p['aanvrager_email'] = $gebruiker['email'];
        $p['aangemaakt_op'] = $p['aangemaakt_op'] ?? now()->toIso8601String();

        DB::table('go_assets_projecten')->insert([
            'id' => $id,
            'ref' => $ref,
            'status' => 'aangevraagd',
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

    // ------------------------------------------------------------ helpers

    private function volgendeRef(): string
    {
        $jaar = now()->format('Y');
        $laatste = DB::table('go_assets_projecten')
            ->where('ref', 'like', "GA-$jaar-%")
            ->orderByDesc('ref')->value('ref');
        $n = $laatste ? intval(substr($laatste, -3)) + 1 : 1;
        return sprintf('GA-%s-%03d', $jaar, $n);
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
