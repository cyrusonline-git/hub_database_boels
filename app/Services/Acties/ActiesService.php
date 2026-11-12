<?php

namespace App\Services\Acties;

use App\Models\AppBadge;
use App\Models\Application;
use App\Models\InvoiceRunNote;
use App\Models\PlanningOrder;
use App\Models\ReservationLine;
use App\Models\SapUpload;
use App\Models\SapUserCode;
use App\Models\User;
use App\Vraagbaak\DataBrug;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Acties per persoon": open werk uit de bestaande apps (via de alleen-lezen
 * DataBrug en de CORE-badges) plus de signalen uit de SAP-data. Elke bron is
 * één methode die per e-mailadres (kleine letters) een lijst signalen geeft:
 *   ['bron' => 'spoedverhuur', 'label' => 'Ruilverzoek wacht op jouw antwoord', 'aantal' => 1, 'url' => …, 'soort' => 'app'|'sap']
 * Nieuwe signalen: één methode erbij in bronnen().
 */
class ActiesService
{
    public function __construct(private DataBrug $brug) {}

    /** Signalen voor één persoon. */
    public function voorPersoon(string $email, ?int $employeeId = null): array
    {
        $email = mb_strtolower(trim($email));
        $alles = $this->alles();
        $items = $alles[$email] ?? [];
        if ($employeeId) {
            foreach ($this->sapPerMedewerker() as $eid => $lijst) {
                if ((int) $eid === (int) $employeeId) {
                    $items = array_merge($items, $lijst);
                }
            }
        }

        return array_values($items);
    }

    /** Alle personen: e-mail => signalen (voor managers/CORE-strook). */
    public function overzicht(): array
    {
        $uit = $this->alles();
        $map = DB::table('employees')->whereNotNull('email')->pluck('id', DB::raw('LOWER(email)'));
        $perEmail = collect($map)->flip(); // id => email
        foreach ($this->sapPerMedewerker() as $eid => $lijst) {
            $email = $perEmail[$eid] ?? null;
            if ($email) {
                $uit[$email] = array_merge($uit[$email] ?? [], $lijst);
            }
        }
        ksort($uit);

        return $uit;
    }

    // ------------------------------------------------------------------

    private function alles(): array
    {
        return Cache::remember('acties:alles', 120, function () {
            $uit = [];
            foreach ($this->bronnen() as $naam => $fn) {
                try {
                    foreach ($fn() as $email => $items) {
                        foreach ($items as $it) {
                            $uit[mb_strtolower($email)][] = $it + ['soort' => 'app'];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("Acties: bron $naam overgeslagen: ".$e->getMessage());
                }
            }

            return $uit;
        });
    }

    /** @return array<string, callable(): array<string, array>> */
    private function bronnen(): array
    {
        return [
            'badges' => fn () => $this->badges(),
            'spoedverhuur' => fn () => $this->spoedverhuur(),
            'servicedesk' => fn () => $this->servicedesk(),
            'planner' => fn () => $this->planner(),
            'inhuur' => fn () => $this->inhuur(),
        ];
    }

    /** CORE-badges: Projectschade, Tankapp, Offertes melden al open aantallen per gebruiker. */
    private function badges(): array
    {
        $uit = [];
        $apps = Application::pluck('name', 'id');
        $urls = Application::pluck('url', 'id');
        $slugs = Application::pluck('slug', 'id');
        $users = User::pluck('email', 'id');
        foreach (AppBadge::where('count', '>', 0)->get() as $b) {
            $email = $users[$b->user_id] ?? null;
            if (! $email) {
                continue;
            }
            $uit[$email][] = ['bron' => $slugs[$b->application_id] ?? 'app', 'label' => 'Open items in '.($apps[$b->application_id] ?? 'app'), 'aantal' => (int) $b->count, 'url' => $urls[$b->application_id] ?? null];
        }

        return $uit;
    }

    private function spoedverhuur(): array
    {
        if (! $this->brug->beschikbaar('spoedverhuur')) {
            return [];
        }
        $url = config('vraagbaak.bronnen.spoedverhuur.url').'/ruilen';
        $uit = [];
        foreach ($this->brug->select('spoedverhuur', "SELECT LOWER(COALESCE(m.email, m.email_handmatig)) AS email, COUNT(*) AS n FROM ruilingen r JOIN medewerkers m ON m.id = r.naar_medewerker_id WHERE r.status = 'aangevraagd' GROUP BY 1") as $r) {
            if ($r['email']) {
                $uit[$r['email']][] = ['bron' => 'spoedverhuur', 'label' => 'Ruilverzoek wacht op jouw antwoord', 'aantal' => (int) $r['n'], 'url' => $url];
            }
        }

        return $uit;
    }

    private function servicedesk(): array
    {
        if (! $this->brug->beschikbaar('servicedesk')) {
            return [];
        }
        $url = config('vraagbaak.bronnen.servicedesk.url');
        $uit = [];
        foreach ($this->brug->select('servicedesk', "SELECT LOWER(u.email) AS email, COUNT(*) AS n FROM orders o JOIN users u ON u.id = o.user_id WHERE o.status <> 'afgehandeld' GROUP BY 1") as $r) {
            if ($r['email']) {
                $uit[$r['email']][] = ['bron' => 'servicedesk', 'label' => 'Onderdelenbestelling nog open', 'aantal' => (int) $r['n'], 'url' => $url];
            }
        }

        return $uit;
    }

    private function planner(): array
    {
        if (! $this->brug->beschikbaar('planner')) {
            return [];
        }
        $url = config('vraagbaak.bronnen.planner.url');
        $uit = [];
        foreach ($this->brug->select('planner', "SELECT LOWER(u.email) AS email, r.status, COUNT(*) AS n FROM rides r JOIN users u ON u.id = r.created_by WHERE r.status IN ('AANGEVRAAGD','PROBLEEM') AND r.archived = 0 AND r.deleted_at IS NULL GROUP BY 1, 2") as $r) {
            if ($r['email']) {
                $uit[$r['email']][] = ['bron' => 'planner', 'label' => $r['status'] === 'PROBLEEM' ? 'Transportrit met probleem' : 'Transportaanvraag nog niet ingepland', 'aantal' => (int) $r['n'], 'url' => $url];
            }
        }

        return $uit;
    }

    private function inhuur(): array
    {
        if (! $this->brug->beschikbaar('inhuur')) {
            return [];
        }
        $url = config('vraagbaak.bronnen.inhuur.url');
        $uit = [];
        foreach ($this->brug->select('inhuur', "SELECT LOWER(u.email) AS email, COUNT(*) AS n FROM rentals r JOIN users u ON u.id = r.user_id WHERE r.status = 'RETOUR NIET AFGEMELD' GROUP BY 1") as $r) {
            if ($r['email']) {
                $uit[$r['email']][] = ['bron' => 'inhuur', 'label' => 'Inhuur retour nog niet afgemeld', 'aantal' => (int) $r['n'], 'url' => $url];
            }
        }

        return $uit;
    }

    /** SAP-signalen per employee_id (uit de laatste snapshots). */
    private function sapPerMedewerker(): array
    {
        return Cache::remember('acties:sap', 120, function () {
            $uit = [];
            $dash = rtrim((string) (Application::where('slug', 'dashboard')->value('url') ?: 'https://dashboard.sorai.nl'), '/');
            $codes = SapUserCode::whereNotNull('employee_id')->pluck('employee_id', 'code');
            $vandaag = now()->toDateString();

            // Uit factuurrun zonder reden (per aanmaker)
            if ($u = SapUpload::laatste('factuurrun')) {
                $zonder = InvoiceRunNote::whereNull('verdwenen_op')->where(fn ($q) => $q->whereNull('reden')->orWhere('reden', ''))->pluck('contract_number');
                $perCode = DB::table('invoice_run_lines')->where('upload_id', $u->id)->whereIn('contract_number', $zonder)->whereNotNull('created_by_code')
                    ->selectRaw('created_by_code, COUNT(DISTINCT contract_number) AS n')->groupBy('created_by_code')->get();
                foreach ($perCode as $r) {
                    if ($eid = $codes[$r->created_by_code] ?? null) {
                        $uit[$eid][] = ['bron' => 'sap', 'label' => 'Uit factuurrun zonder reden', 'aantal' => (int) $r->n, 'url' => $dash.'/factuurrun?zonder_reden=1', 'soort' => 'sap'];
                    }
                }
            }
            // Op te schonen (planning, leverdatum < vandaag) per verkoper
            if ($u = SapUpload::laatste('planning')) {
                foreach (PlanningOrder::where('upload_id', $u->id)->whereNotNull('employee_id')->whereDate('delivery_date', '<', $vandaag)->selectRaw('employee_id, COUNT(*) AS n')->groupBy('employee_id')->get() as $r) {
                    $uit[$r->employee_id][] = ['bron' => 'sap', 'label' => 'Orders op te schonen (leverdatum voorbij)', 'aantal' => (int) $r->n, 'url' => $dash.'/planning?verleden=1', 'soort' => 'sap'];
                }
            }
            // Offerte verlopen (2.6, H = Offerte, leverdatum < vandaag) per aanmaker
            if ($u = SapUpload::laatste('reserveringen')) {
                foreach (ReservationLine::where('upload_id', $u->id)->where('is_contract', false)->whereDate('delivery_date', '<', $vandaag)->whereNotNull('created_by_code')->selectRaw('created_by_code, COUNT(DISTINCT contract_number) AS n')->groupBy('created_by_code')->get() as $r) {
                    if ($eid = $codes[$r->created_by_code] ?? null) {
                        $uit[$eid][] = ['bron' => 'sap', 'label' => 'Offerte verlopen — nabellen', 'aantal' => (int) $r->n, 'url' => $dash.'/planning?soort=offerte&alles=1', 'soort' => 'sap'];
                    }
                }
            }

            return $uit;
        });
    }
}
