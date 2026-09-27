<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Vraagbaak\BronNietBeschikbaar;
use App\Vraagbaak\Catalogus;
use App\Vraagbaak\DataBrug;
use App\Vraagbaak\Depots;
use App\Vraagbaak\Herkenner;
use App\Vraagbaak\Logboek;
use App\Vraagbaak\Periode;
use Illuminate\Http\Request;

/**
 * Boels Vraagbaak — eigen AI: vraag in gewone taal → herkende catalogusvraag → berekening in de app-databases.
 * Acties: vraag (POST {vraag, kies?}), suggesties (GET q), catalogus (GET), feedback (POST), csv (GET id + laatste params).
 */
class VraagbaakController extends Controller
{
    public function api(Request $request)
    {
        $user = $request->user();
        $actie = (string) $request->query('action', '');
        $in = $request->isMethod('post') ? (array) $request->json()->all() : [];

        switch ($actie) {
            case 'catalogus':
                $per = [];
                foreach (Catalogus::voor($user) as $bron => $vragen) {
                    $per[] = ['bron' => $bron, 'naam' => self::bronNaam($bron), 'vragen' => array_map(fn ($v) => ['id' => $v->id, 'titel' => $v->titel, 'rol' => $v->rol], $vragen)];
                }
                $top = Logboek::where('status', 'ok')->where('created_at', '>=', now()->subDays(90))->selectRaw('herkend_als, count(*) as n')->groupBy('herkend_als')->orderByDesc('n')->limit(8)->pluck('n', 'herkend_als')->all();
                $recent = Logboek::where('user_id', $user->id)->orderByDesc('id')->limit(8)->pluck('vraag')->unique()->values()->all();

                return ['ok' => true, 'bronnen' => $per, 'populair' => $top, 'recent' => $recent, 'banner' => config('vraagbaak.banner'), 'manager' => Catalogus::isManager($user)];

            case 'suggesties':
                $q = Herkenner::normaliseer((string) $request->query('q', ''));
                $uit = [];
                foreach (Catalogus::voor($user) as $vragen) {
                    foreach ($vragen as $v) {
                        if ($q === '' || str_contains(Herkenner::normaliseer($v->titel), $q) || Herkenner::score($v, $q, Herkenner::woorden($q)) > 0) {
                            $uit[] = ['id' => $v->id, 'titel' => $v->titel, 'bron' => self::bronNaam($v->bron)];
                        }
                    }
                }

                return ['ok' => true, 'suggesties' => array_slice($uit, 0, 10)];

            case 'vraag':
                return $this->vraag($request, $user, $in);

            case 'feedback':
                $log = Logboek::where('user_id', $user->id)->find((int) ($in['log_id'] ?? 0));
                if ($log) {
                    $log->update(['feedback' => mb_substr((string) ($in['feedback'] ?? ''), 0, 500)]);
                }

                return ['ok' => true];

            case 'csv':
                return $this->csv($request, $user);
        }

        return response()->json(['ok' => false, 'fout' => 'Onbekende actie'], 400);
    }

    private function vraag(Request $request, $user, array $in)
    {
        $tekst = trim((string) ($in['vraag'] ?? ''));
        if ($tekst === '' && empty($in['kies'])) {
            return response()->json(['ok' => false, 'fout' => 'Stel een vraag.'], 422);
        }
        $start = microtime(true);
        $toegestaan = [];
        foreach (Catalogus::voor($user) as $vragen) {
            foreach ($vragen as $v) {
                $toegestaan[$v->id] = $v;
            }
        }
        if (! empty($in['kies'])) {
            $v = $toegestaan[$in['kies']] ?? null;
            if (! $v) {
                return response()->json(['ok' => false, 'fout' => 'Deze vraag is niet beschikbaar voor jouw rol.'], 403);
            }
            $herkend = ['status' => 'ok', 'vraag' => $v, 'params' => Herkenner::parameters($tekst !== '' ? $tekst : $v->titel, $v), 'kandidaten' => []];
        } else {
            $herkend = Herkenner::herken($tekst, array_values($toegestaan));
        }
        $log = Logboek::create(['user_id' => $user->id, 'vraag' => mb_substr($tekst ?: ($in['kies'] ?? ''), 0, 500), 'status' => $herkend['status'], 'herkend_als' => $herkend['vraag']?->id]);
        if ($herkend['status'] !== 'ok') {
            $log->update(['duur_ms' => (int) ((microtime(true) - $start) * 1000)]);

            return ['ok' => true, 'status' => $herkend['status'], 'kandidaten' => array_map(fn ($k) => $k + ['bron_naam' => self::bronNaam($k['bron'])], $herkend['kandidaten']), 'log_id' => $log->id,
                'melding' => $herkend['status'] === 'keuze' ? 'Ik twijfel tussen een paar vragen. Welke bedoel je?' : 'Deze vraag ken ik nog niet. Bedoel je misschien een van deze? Je kunt hem ook doorgeven aan de beheerder.'];
        }
        $v = $herkend['vraag'];
        $p = $this->depotBeperking($user, $herkend['params']);
        try {
            $res = $v->bereken(app(DataBrug::class), $p);
        } catch (BronNietBeschikbaar $e) {
            $log->update(['status' => 'fout', 'duur_ms' => (int) ((microtime(true) - $start) * 1000)]);

            return ['ok' => true, 'status' => 'fout', 'melding' => $e->getMessage(), 'log_id' => $log->id];
        } catch (\Throwable $e) {
            report($e);
            $log->update(['status' => 'fout', 'duur_ms' => (int) ((microtime(true) - $start) * 1000)]);

            return ['ok' => true, 'status' => 'fout', 'melding' => 'Het rekenen is mislukt: '.mb_substr($e->getMessage(), 0, 200), 'log_id' => $log->id];
        }
        $duur = (int) ((microtime(true) - $start) * 1000);
        $log->update(['duur_ms' => $duur, 'parameters' => ['depot' => $p['depot']['naam'] ?? null, 'periode' => $p['periode']['label'] ?? null, 'top' => $p['top'] ?? null, 'naam' => $p['naam'] ?? null, 'machine' => $p['machine'] ?? null]]);
        $filters = [];
        if (! empty($p['periode']['label']) && in_array('periode', $v->params, true)) {
            $filters[] = ['label' => 'Periode', 'waarde' => $p['periode']['label'], 'gegokt' => ($p['periode']['match'] ?? '') === ''];
        }
        if (! empty($p['depot'])) {
            $filters[] = ['label' => $p['depot']['type'] === 'area' ? 'Area' : 'Vestiging', 'waarde' => $p['depot']['naam'], 'gegokt' => false];
        } elseif (in_array('depot', $v->params, true)) {
            $filters[] = ['label' => 'Vestiging', 'waarde' => 'alle', 'gegokt' => true];
        }
        foreach (['brandstof' => 'Brandstof', 'top' => 'Top', 'naam' => 'Naam', 'machine' => 'Machine'] as $k => $l) {
            if (! empty($p[$k])) {
                $filters[] = ['label' => $l, 'waarde' => $p[$k], 'gegokt' => false];
            }
        }
        $bronnen = array_map(fn ($b) => ['naam' => self::bronNaam($b), 'url' => config("vraagbaak.bronnen.$b.url")], explode('+', $v->bron));
        $request->session()->put('vraagbaak.laatste', ['id' => $v->id, 'params' => $this->serialiseer($p)]);

        return ['ok' => true, 'status' => 'ok', 'vraag' => ['id' => $v->id, 'titel' => $v->titel], 'filters' => $filters, 'resultaat' => $res->toArray(), 'bronnen' => $bronnen, 'duur_ms' => $duur, 'log_id' => $log->id, 'kandidaten' => array_slice($herkend['kandidaten'], 1, 3)];
    }

    /** Gebruikers met depot-/area-beperking mogen alleen hun eigen depots bevragen. */
    private function depotBeperking($user, array $p): array
    {
        if ($user->is_super_admin || $user->hasRole(['super-admin', 'administrator'])) {
            return $p;
        }
        $depots = $user->allowed_depots ?: ($user->employee?->depot ? [$user->employee->depot] : []);
        $areas = $user->allowed_areas ?: ($user->employee?->area ? [$user->employee->area] : []);
        if (! $depots && ! $areas) {
            return $p;
        }
        if (! empty($p['depot'])) {
            $ok = false;
            foreach ($depots as $d) {
                if (mb_strtolower($d) === mb_strtolower($p['depot']['naam'])) {
                    $ok = true;
                }
            }
            foreach ($areas as $a) {
                $an = trim(preg_replace('/^area\s+/i', '', mb_strtolower($a)));
                if ($p['depot']['type'] === 'area' && $an === mb_strtolower($p['depot']['naam'])) {
                    $ok = true;
                }
                foreach (Depots::lijst() as $d) {
                    if ($d['naam'] === $p['depot']['naam'] && mb_strtolower((string) $d['area']) === $an) {
                        $ok = true;
                    }
                }
            }
            if (! $ok) {
                abort(403, 'Je mag alleen vragen stellen over je eigen vestiging of area.');
            }

            return $p;
        }
        // Geen depot genoemd: beperken tot eigen depot (of area)
        if ($depots) {
            $eerste = Depots::herken(' '.$depots[0].' ') ?: ['type' => 'depot', 'naam' => $depots[0], 'termen' => Depots::termenUitNaam($depots[0]), 'nummers' => [], 'match' => ''];
            $p['depot'] = $eerste;
        } elseif ($areas) {
            $p['depot'] = Depots::herken(' '.trim(preg_replace('/^area\s+/i', '', $areas[0])).' ') ?: null;
        }

        return $p;
    }

    private function serialiseer(array $p): array
    {
        $q = $p;
        if (! empty($q['periode'])) {
            $q['periode'] = array_intersect_key($q['periode'], array_flip(['van_s', 'tm_s', 'label', 'match']));
        }

        return $q;
    }

    private function csv(Request $request, $user)
    {
        $laatste = $request->session()->get('vraagbaak.laatste');
        $v = $laatste ? Catalogus::vind($laatste['id']) : null;
        if (! $v) {
            return response()->json(['ok' => false, 'fout' => 'Geen recent resultaat.'], 404);
        }
        $p = $laatste['params'];
        $res = $v->bereken(app(DataBrug::class), $p);
        $out = "\xEF\xBB\xBF".implode(';', $res->kolommen)."\n";
        foreach ($res->rijen as $r) {
            $out .= implode(';', array_map(fn ($c) => is_float($c) ? number_format($c, 2, ',', '') : str_replace([';', "\n"], [',', ' '], (string) $c), $r))."\n";
        }

        return response($out, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="vraagbaak-'.$v->id.'.csv"']);
    }

    public static function bronNaam(string $bron): string
    {
        return implode(' + ', array_map(fn ($b) => config("vraagbaak.bronnen.$b.naam", $b), explode('+', $bron)));
    }
}
