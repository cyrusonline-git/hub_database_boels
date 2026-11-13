<?php

namespace App\Services\Sap;

use App\Models\HireLine;
use App\Models\InvoiceRunLine;
use App\Models\InvoiceRunNote;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use App\Models\User;
use App\Services\Acties\ActiesService;
use App\Vraagbaak\DataBrug;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cockpit-cijfers voor het CORE-startscherm ("Vandaag") en het managementoverzicht:
 * alles uit de eigen Smartview-tabellen + ActiesService + de hoofdcijfers van de apps (DataBrug).
 * Eén aanroep, 5 minuten gecached per gebruiker.
 */
class Overzicht
{
    public function __construct(private ActiesService $acties, private DataBrug $brug, private HireData $hire, private PlanningOverzicht $planning) {}

    /** @param array $rollen dashboard-rollen (underscore-slugs) van de gebruiker */
    public function voor(User $user, array $rollen, bool $manager): array
    {
        $sleutel = 'overzicht:'.SapUpload::stand().':'.$user->id.':'.md5(implode(',', $rollen).($manager ? 'm' : ''));

        return Cache::remember($sleutel, 300, function () use ($user, $rollen, $manager) {
            $vandaag = now()->toDateString();
            $morgen = now()->addDay()->toDateString();
            $binnendienstEigen = in_array('binnendienst', $rollen, true) && ! $manager;
            $employeeId = $user->employee_id;
            $dash = KlantContext::dashboardUrl();

            // Planning (8.7 + 2.6 samengevoegd), komende 7 dagen, zonder transfers/offertes
            $orders = $this->planning->rijen(['tm' => now()->addDays(7)->toDateString()])
                ->reject(fn ($o) => $o->is_offerte || in_array($o->soort, ['transfer_in', 'transfer_uit'], true));
            $perDag = [];
            for ($i = 0; $i < 7; $i++) {
                $d = now()->addDays($i)->toDateString();
                $dagOrders = $orders->where('delivery_date', $d);
                $perDag[] = ['datum' => $d, 'volledig' => $dagOrders->where('pick_status', 'volledig')->count(), 'deels' => $dagOrders->where('pick_status', 'deels')->count(), 'niets' => $dagOrders->whereNotIn('pick_status', ['volledig', 'deels'])->count()];
            }
            $vandaagOrders = $orders->where('delivery_date', $vandaag)->values();

            // Ophalen (WOW To Collect) t/m morgen + te laat
            $ophalen = $this->hire->opTeHalen(null, $morgen)->values();

            // Factuurrun (eigen contracten voor binnendienst)
            $fr = $this->hire->factuurrun($binnendienstEigen && $employeeId ? ['employee_id' => $employeeId] : []);
            $factuurrun = [
                'eigen' => $binnendienstEigen,
                'contracten' => $fr->count(),
                'zonder_reden' => $fr->filter(fn ($r) => ! $r->reden)->count(),
                'nooit' => $fr->where('nooit_gefactureerd', true)->count(),
                'weekhuur' => round($fr->sum('weekhuur'), 2),
                'top' => $fr->filter(fn ($r) => ! $r->reden)->take(3)->map(fn ($r) => ['contract_number' => $r->contract_number, 'customer_name' => $r->customer_name, 'weekhuur' => $r->weekhuur, 'dagen' => $r->dagen_zonder_factuur])->values(),
            ];

            $uit = [
                'dashboard' => $dash,
                'uploads' => $this->uploads(),
                'vandaag' => ['orders' => $vandaagOrders->take(8)->values(), 'aantal' => $vandaagOrders->count(), 'morgen' => $orders->where('delivery_date', $morgen)->count(), 'volledig' => $vandaagOrders->where('pick_status', 'volledig')->count()],
                'ophalen' => ['lijst' => $ophalen->take(8)->values(), 'aantal' => $ophalen->count(), 'te_laat' => $ophalen->where('te_laat', true)->count()],
                'factuurrun' => $factuurrun,
                'signalen' => $this->acties->voorPersoon((string) $user->email, $employeeId),
                'planning_week' => $perDag,
            ];

            if ($manager) {
                $uit['kpi'] = $this->kpi($orders);
                $uit['apps'] = $this->apps();
                $uit['team'] = $this->team();
            }

            return $uit;
        });
    }

    private function uploads(): array
    {
        $uit = [];
        foreach ((array) config('sap.soorten') as $soort => $cfg) {
            if (! empty($cfg['verborgen'])) {
                continue;
            }
            $u = SapUpload::laatste($soort);
            $uit[$soort] = ['label' => $cfg['label'], 'moment' => $u?->created_at?->toDateTimeString(), 'door' => $u?->user?->name, 'verouderd' => $u ? $u->created_at->diffInDays(now()) > $cfg['frequentie_dagen'] + 1 : true];
        }

        return $uit;
    }

    private function kpi($orders): array
    {
        $s = $this->hire->samenvatting();
        $trend = collect($s['trend'] ?? []);
        $vorige = $trend->count() > 1 ? $trend[$trend->count() - 2]['weekhuur'] : null;
        $pl = SapUpload::laatste('planning');
        $transfers = $pl ? PlanningOrder::where('upload_id', $pl->id)->whereIn('soort', ['transfer_in', 'transfer_uit'])->whereDate('delivery_date', '>=', now()->toDateString())->selectRaw('soort, COUNT(*) AS n')->groupBy('soort')->pluck('n', 'soort') : collect();
        $opTeSchonen = $pl ? PlanningOrder::where('upload_id', $pl->id)->whereDate('delivery_date', '<', now()->toDateString())->count() : 0;
        $rs = SapUpload::laatste('reserveringen');
        $offertes = $rs ? ReservationLine::where('upload_id', $rs->id)->where('is_contract', false)->selectRaw('COUNT(DISTINCT contract_number) AS n, SUM(CASE WHEN delivery_date < ? THEN 1 ELSE 0 END) AS verlopen_regels', [now()->toDateString()])->first() : null;
        $verlopen = $rs ? ReservationLine::where('upload_id', $rs->id)->where('is_contract', false)->whereDate('delivery_date', '<', now()->toDateString())->distinct()->count('contract_number') : 0;
        $frAlle = $this->hire->factuurrun();
        // Factuurrun naar ouderdom (dagen zonder factuur), in weekhuur
        $buckets = ['0-7' => 0.0, '8-30' => 0.0, '31-90' => 0.0, '>90' => 0.0];
        $bucketsN = ['0-7' => 0, '8-30' => 0, '31-90' => 0, '>90' => 0];
        foreach ($frAlle as $r) {
            $k = $r->dagen_zonder_factuur <= 7 ? '0-7' : ($r->dagen_zonder_factuur <= 30 ? '8-30' : ($r->dagen_zonder_factuur <= 90 ? '31-90' : '>90'));
            $buckets[$k] += (float) $r->weekhuur;
            $bucketsN[$k]++;
        }

        return [
            'weekhuur' => $s['weekhuur'] ?? 0,
            'weekhuur_delta' => $vorige !== null ? round(($s['weekhuur'] ?? 0) - $vorige, 2) : null,
            'contracten' => $s['contracten'] ?? 0,
            'klanten' => isset($s['per_klant']) ? count($s['per_klant']) : 0,
            'ophalen_contracten' => $s['to_collect_contracten'] ?? 0,
            'ophalen_te_laat' => $this->hire->opTeHalen(null, now()->subDay()->toDateString())->count(),
            'orders_week' => $orders->count(),
            'orders_week_volledig' => $orders->where('pick_status', 'volledig')->count(),
            'op_te_schonen' => $opTeSchonen,
            'transfers_in' => (int) ($transfers['transfer_in'] ?? 0),
            'transfers_uit' => (int) ($transfers['transfer_uit'] ?? 0),
            'factuurrun' => ['contracten' => $frAlle->count(), 'zonder_reden' => $frAlle->filter(fn ($r) => ! $r->reden)->count(), 'nooit' => $frAlle->where('nooit_gefactureerd', true)->count(), 'weekhuur' => round($frAlle->sum('weekhuur'), 2), 'ouderdom' => $buckets, 'ouderdom_n' => $bucketsN],
            'offertes' => ['open' => (int) ($offertes->n ?? 0), 'verlopen' => $verlopen],
            'trend' => $trend->map(fn ($t) => ['datum' => $t['datum'], 'weekhuur' => $t['weekhuur']])->values(),
        ];
    }

    /** Hoofdcijfers per app via de alleen-lezen DataBrug; een bron die wegvalt geeft null. */
    private function apps(): array
    {
        $cfg = fn ($b) => config("vraagbaak.bronnen.$b");
        $apps = [];
        $probeer = function (string $bron, string $label, callable $fn) use (&$apps, $cfg) {
            $c = $cfg($bron);
            try {
                $apps[$bron] = ['label' => $label, 'url' => $c['url'] ?? null, 'ok' => true] + $fn();
            } catch (\Throwable $e) {
                $apps[$bron] = ['label' => $label, 'url' => $c['url'] ?? null, 'ok' => false, 'cijfers' => []];
            }
        };
        $probeer('projectschade', 'Projectschade', fn () => ['cijfers' => [
            'Servicedesk' => (int) $this->brug->waarde('projectschade', "SELECT COUNT(*) FROM damage_items WHERE status IN ('Nieuw (via expeditie)','Nieuw (via werkplaats)')"),
            'Werkplaats' => (int) $this->brug->waarde('projectschade', "SELECT COUNT(*) FROM damage_items WHERE wp_status = 'Te inspecteren' AND COALESCE(status,'') <> 'Afgehandeld'"),
            'Binnendienst' => (int) $this->brug->waarde('projectschade', "SELECT COUNT(*) FROM damage_items WHERE status = 'Afgehandeld' AND (bd_status IS NULL OR bd_status NOT IN ('Afgehandeld','Overgedragen','Geen schade','Intern afgesloten','Naar projecten'))"),
        ]]);
        $probeer('tankapp', 'Tankapp', fn () => ['cijfers' => ['Door te belasten' => (int) $this->brug->waarde('tankapp', "SELECT COUNT(*) FROM aftankingen WHERE status = 'afgetankt' AND archived = 0")]]);
        $probeer('spoedverhuur', 'Spoedverhuur', fn () => ['cijfers' => ['Open ruilverzoeken' => (int) $this->brug->waarde('spoedverhuur', "SELECT COUNT(*) FROM ruilingen WHERE status = 'aangevraagd'")]]);
        $probeer('planner', 'Transportplanner', fn () => ['cijfers' => [
            'Aangevraagd' => (int) $this->brug->waarde('planner', "SELECT COUNT(*) FROM rides WHERE status = 'AANGEVRAAGD' AND archived = 0 AND deleted_at IS NULL"),
            'Probleem' => (int) $this->brug->waarde('planner', "SELECT COUNT(*) FROM rides WHERE status = 'PROBLEEM' AND archived = 0 AND deleted_at IS NULL"),
        ]]);
        $probeer('inhuur', 'Inhuur', fn () => ['cijfers' => [
            'In huur' => (int) $this->brug->waarde('inhuur', "SELECT COUNT(*) FROM rentals WHERE status = 'IN HUUR'"),
            'Retour niet afgemeld' => (int) $this->brug->waarde('inhuur', "SELECT COUNT(*) FROM rentals WHERE status = 'RETOUR NIET AFGEMELD'"),
        ]]);
        $probeer('servicedesk', 'Servicedesk (onderdelen)', fn () => ['cijfers' => ['Open bestellingen' => (int) $this->brug->waarde('servicedesk', "SELECT COUNT(*) FROM orders WHERE status <> 'afgehandeld'")]]);
        $probeer('offertes', 'Offertes', fn () => ['cijfers' => [
            'Nieuwe aanvragen' => (int) $this->brug->waarde('offertes', "SELECT COUNT(*) FROM quote_requests WHERE status = 'nieuw'"),
            'In behandeling' => (int) $this->brug->waarde('offertes', "SELECT COUNT(*) FROM quote_requests WHERE status = 'in_behandeling'"),
            'Offerte uit, wacht op klant' => (int) $this->brug->waarde('offertes', "SELECT COUNT(*) FROM quote_requests WHERE status = 'offerte_gemaakt'"),
            'Gescoord (30 dagen)' => (int) $this->brug->waarde('offertes', "SELECT COUNT(*) FROM quote_requests WHERE status = 'gescoord' AND created_at >= date('now', '-30 days')"),
        ]]);
        $probeer('scanner', 'Scanner (projectmaterieel)', fn () => ['cijfers' => [
            'Op locatie (uitgezet)' => (int) $this->brug->waarde('scanner', "SELECT COUNT(*) FROM location_items WHERE status = 'uitgezet'"),
            'Ouder dan 30 dagen' => (int) $this->brug->waarde('scanner', "SELECT COUNT(*) FROM location_items WHERE status = 'uitgezet' AND placed_at < date('now', '-30 days')"),
        ]]);
        $probeer('voorraad', 'Voorraad tool', fn () => ['cijfers' => [
            'Regels te zoeken' => (int) $this->brug->waarde('voorraad', "SELECT COUNT(*) FROM order_regels WHERE status_code = 'not_allocated'"),
            'Aanvragen mislukt' => (int) $this->brug->waarde('voorraad', "SELECT COUNT(*) FROM aanvragen WHERE status = 'mislukt'"),
        ]]);
        $probeer('keuringen', 'Keuringen eigen materieel', fn () => ['cijfers' => [
            'Keuring verlopen' => (int) $this->brug->waarde('keuringen', "SELECT COUNT(*) FROM machines WHERE inspection_valid_until < date('now')"),
            'Verloopt binnen 30 dagen' => (int) $this->brug->waarde('keuringen', "SELECT COUNT(*) FROM machines WHERE inspection_valid_until >= date('now') AND inspection_valid_until < date('now', '+30 days')"),
        ]]);

        return $apps;
    }

    /** Per medewerker: aantallen per signaal (uit ActiesService) + orders deze week als verkoper. */
    private function team(): array
    {
        $namen = DB::table('employees')->whereNotNull('email')->where('active', true)->get(['id', 'name', 'email', 'function', 'depot'])->keyBy(fn ($e) => mb_strtolower($e->email));
        $uit = [];
        foreach ($this->acties->overzicht() as $email => $items) {
            $e = $namen[$email] ?? null;
            $per = [];
            foreach ($items as $it) {
                $per[$it['label']] = ($per[$it['label']] ?? 0) + (int) $it['aantal'];
            }
            $uit[] = ['naam' => $e->name ?? $email, 'functie' => $e->function ?? null, 'email' => $email, 'totaal' => array_sum($per), 'per' => $per];
        }
        usort($uit, fn ($a, $b) => $b['totaal'] <=> $a['totaal']);

        return $uit;
    }
}
