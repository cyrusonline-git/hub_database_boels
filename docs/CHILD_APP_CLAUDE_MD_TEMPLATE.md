# CLAUDE.md template voor child-apps van Boels CORE

> Plaats dit bestand (na invullen van `{APP_NAAM}` en `{APP_SLUG}`) als
> `CLAUDE.md` in de root van elke nieuwe app-map.
> Claude leest dit bij elke sessie en past zich er strikt aan.

---

# {APP_NAAM} — Architectuurregels (verplicht volgen)

Deze app is een **child-app van Boels CORE Platform**. Lees onderstaande
regels voordat je code wijzigt.

## Architectuur

- **Naam:** {APP_NAAM}
- **Slug:** `{APP_SLUG}`
- **Subdomein:** https://{APP_SLUG}.sorai.nl
- **Tabel-prefix:** `{APP_SLUG}_`
- **Boels CORE:** https://databasehub.sorai.nl
- **MySQL database:** gedeeld met Boels CORE (`deb2003831_hub_database_boels`)
- **MySQL user:** `deb2003831_{APP_SLUG}` (beperkte rechten — zie hieronder)

## STRIKTE REGELS

### ❌ NOOIT DOEN

1. **Geen ALTER TABLE op CORE-tabellen.**
   CORE-tabellen zijn: `users`, `roles`, `permissions`, `role_permissions`,
   `user_roles`, `applications`, `departments`, `employees`, `customers`,
   `contacts`, `projects`, `machines`, `machine_groups`, `machine_subgroups`,
   `work_orders`, `damages`, `customer_visits`, `leads`, `opportunities`,
   `tasks`, `notes`, `documents`, `attachments`, `custom_fields`,
   `custom_field_values`, `field_aliases`, `import_profiles`, `import_jobs`,
   `import_job_rows`, `audit_logs`, `app_table_ownership`.

2. **Geen nieuwe kolommen toevoegen aan CORE-tabellen.**
   Wil je extra data op een Customer of Machine? Gebruik **Custom Fields**
   via `custom_fields` + `custom_field_values` (polymorfe relatie).

3. **Geen foreign keys aanmaken die naar CORE-tabellen verwijzen vanuit
   een verkeerde richting.** App-tabellen mogen WEL refereren naar
   CORE-tabellen (bv. `{APP_SLUG}_contracts.customer_id` → `customers.id`).
   CORE-tabellen mogen NIET refereren naar app-tabellen.

4. **Geen wijzigingen aan `users` / authenticatie.** Auth gebeurt via
   Boels CORE (gedeelde `users` tabel). Deze app leest die tabel alleen.

5. **Geen seeders die CORE-data vullen.** Roltabellen, applications,
   field_aliases enz. worden door Boels CORE beheerd.

### ✅ WEL DOEN

1. **Nieuwe tabellen aanmaken met prefix `{APP_SLUG}_`.**
   Voorbeelden: `{APP_SLUG}_contracts`, `{APP_SLUG}_pricing`,
   `{APP_SLUG}_settings`.

2. **Rijen lezen/toevoegen/wijzigen in CORE-tabellen.**
   `INSERT`, `UPDATE`, `DELETE` op data is prima — alleen geen structuur.

3. **Custom Fields aanmaken voor extra metadata.**
   Via Boels CORE Admin → Custom Fields, of programmatisch via
   `App\Models\CustomField`.

4. **Eigen migrations alleen voor `{APP_SLUG}_*` tabellen.**

5. **Audit log gebruiken** — voeg de `HasAuditLog` trait toe aan modellen
   voor automatische logging in `audit_logs`.

## Bij twijfel

Als je een verandering wil doen die een CORE-tabel raakt — **STOP** en zeg
tegen de gebruiker:

> "Dit raakt een Boels CORE tabel ({tabelnaam}). Deze wijziging moet in de
> CORE-repo (`hub_database_boels`) gedaan worden, niet hier. Wil je dat ik
> dat daar voorbereid in plaats van hier?"

## Identity Provider — SSO via Session Cookie Sharing

Deze app authenticeert NIET zelf. Boels CORE is de identity provider.

**Hoe het werkt:**
- Session cookie heeft domein `.sorai.nl` → wordt gedeeld over ALLE `*.sorai.nl` subdomeinen
- Zodra een user is ingelogd op `databasehub.sorai.nl`, herkent jouw app diezelfde user via Laravel's standaard `Auth::check()`
- Geen tokens in URLs, geen extra login-scherm

**`.env` van deze app moet bevatten:**
```env
SESSION_DOMAIN=.sorai.nl
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=databasehub.sorai.nl,{APP_SLUG}.sorai.nl
APP_KEY={SAME_AS_BOELS_CORE}   # cruciaal: zelfde APP_KEY = sessies decrypten lukt
```

**Geen LoginController nodig.** Wel een redirect-naar-CORE-login als de user niet ingelogd is:
```php
if (! Auth::check()) {
    return redirect('https://databasehub.sorai.nl/login');
}
```

**Ook GEEN eigen wachtwoord-schermen.** Er bestaat maar één wachtwoord,
in de CORE `users` tabel — voor alle apps hetzelfde. Bouw dus nooit een
eigen "wachtwoord vergeten" of "wachtwoord wijzigen"; link altijd naar:
`https://databasehub.sorai.nl/wachtwoord-vergeten`.
Na reset daar werkt de login direct weer in alle apps (SSO).

## Data Scoping — gebruik `ScopesByUserAccess` trait

Voor elk model dat area/depot/country-specifieke data bevat:

```php
use App\Models\Concerns\ScopesByUserAccess;

class Machine extends Model {
    use ScopesByUserAccess;

    protected array $userScopeColumns = [
        'area' => 'allowed_areas',
        'depot' => 'allowed_depots',
        'country' => 'allowed_countries',
    ];

    protected ?string $userScopeBypassPermission = '{APP_SLUG}.global';
}
```

Effect: `Machine::all()` retourneert automatisch alleen records die overlappen
met de allowed_* arrays van de ingelogde user. Super admins en users met de
`{APP_SLUG}.global` permissie zien alles.

Tijdelijk zonder scope querien (bv. voor admin-rapporten):
```php
Machine::withoutGlobalScope(ScopesByUserAccess::class)->get();
```

## Permissies — vraag CORE om data

CORE exposeert deze API-endpoints (auth via gedeelde session cookie OF Sanctum token):

| Endpoint | Wat |
|---|---|
| `GET /api/me` | User-info + roles + permissions + allowed_areas/depots/countries |
| `GET /api/applications` | Welke apps deze user mag zien |
| `GET /api/can/{permission}` | Snelle ja/nee check |

In je app kun je gewoon `auth()->user()->hasPermission('{APP_SLUG}.manage')`
gebruiken (gedeelde users tabel).

## Deploy-pijplijn

Werkt identiek aan Boels CORE:
1. Push naar `main` op GitHub
2. GitHub Actions bouwt + zipt + uploadt via FTPS naar Antagonist
3. Open `https://{APP_SLUG}.sorai.nl/__deploy_unpack.php?k=...`

## Beveiligingslagen actief

| Laag | Hoe afgedwongen |
|---|---|
| MySQL user rechten | Database server weigert ALTER op CORE-tabellen |
| Tabel-prefix conventie | Maakt code-review eenvoudig |
| Dit CLAUDE.md bestand | Claude wijkt niet af van deze regels |
| Dagelijkse DB-backup | 7 dagen rolling, in Boels CORE storage/backups/ |

## Autorisatie: Boels-rollen uit CORE, klant-rollen lokaal

**Boels-medewerkers:** hun functionele rol in deze app (bv. monteur,
expeditie, binnendienst) wordt beheerd in CORE — Beheer → Applicaties →
deze app → "Rollen in deze app", toegekend via Beheer → Gebruikers.
De app haalt ze op met `GET /api/access/{APP_SLUG}` →
`{ roles: [{slug, name, scope}], is_super_admin, permissions }` en mapt de
rol-slugs op zijn eigen gedrag. `scope: "app"` = rol van deze app.
`is_super_admin` = altijd volledige toegang. Iemand zonder rol voor deze
app → "nog geen toegang"-pagina.

Publiceer een read-only endpoint `core-roles.php` (webroot) met de rollen
als JSON (`{"app":"{APP_SLUG}","name":"{APP_NAAM}","roles":[{"slug","name","description"}]}`)
zodat CORE ze met één knop kan importeren — geen handwerk voor de beheerder.
Optioneel ook `core-users.php` (vereist ?k=<sync-sleutel>, anders 403) met
`{"users":[{"email","roles":[slugs]}]}` — alleen Boels-medewerkers, nooit
klant-accounts — zodat CORE ook de rol-toekenningen automatisch overneemt.

**Klant-medewerkers** (staan niet in CORE): lokale accounts én lokale
rollen in de eigen tabellen, beheerd via het eigen toegangsbeheer — zie
hieronder. CORE weet niets van klant-accounts.

## (Fallback) Volledig lokale autorisatie

Boels CORE levert uitsluitend de **identiteit**: wie is ingelogd
(via de SSO-sessiecookie of `GET /api/me` → `{ id, name, email,
is_super_admin, employee_id, ... }`).

**Wie toegang heeft tot deze app en met welke rol, bepaalt deze app
volledig zelf.** Regels:

1. Maak eigen tabellen met app-prefix, bv.:
   - `{APP_SLUG}_roles` (id, name, slug, description)
   - `{APP_SLUG}_user_roles` (user_id → core `users.id`, role_id)
2. Bouw in deze app een eigen beheerscherm (alleen voor app-beheerders)
   om gebruikers toegang te geven en rollen toe te kennen.
3. Ingelogd via CORE maar (nog) geen rol in deze app? → GEEN toegang tot
   data; toon een nette "je hebt nog geen toegang, vraag het aan bij de
   beheerder"-pagina.
4. `is_super_admin` uit CORE mag standaard overal bij (handig als
   eerste beheerder om de rest in te richten).
5. Autorisatiechecks ALTIJD server-side tegen de eigen roltabellen.

(CORE kent óók app-specifieke rollen + `GET /api/access/{slug}` — dat is
optioneel en wordt door deze app NIET gebruikt; rollen zijn hier lokaal.)

## Data-API (read-only) — klanten, materieel, personeel

Naast direct lezen uit de gedeelde DB kan de app ook via de API
(zelfde SSO-cookie of Sanctum-token, alles onder /api):

```
GET /api/customers?q=shell&concern=4198&per_page=50   (paginated)
GET /api/customers/{debiteurnummer}
GET /api/machines?q=&subgroup=84305&per_page=50
GET /api/machines/{materieelnummer}     → incl. subgroep + specificaties
GET /api/subgroups/{subgroepnummer}     → specs, highlights, accessoires
GET /api/employees?q=&depot=&active=1   (beperkte velden)
```

Schrijven in CORE-data gebeurt NIET via deze API — alleen via CORE zelf
of (voor rijen, niet structuur) via de gedeelde DB volgens de regels boven.

## Niet-Laravel child-apps (kale PHP, andere frameworks)

Een child-app hoeft GEEN Laravel te zijn. Zonder Laravel kun je de
CORE-cookie niet zelf ontcijferen (geen APP_KEY nodig!) — gebruik dan het
**cookie-relay patroon**: stuur de cookies van de bezoeker server-side door
naar de CORE-API en krijg de ingelogde gebruiker terug als JSON.

```php
function coreUser(): ?array {
    // Cache per PHP-sessie (5 min) om niet elke request CORE te bellen
    session_start();
    if (isset($_SESSION['core_user'], $_SESSION['core_user_at'])
        && time() - $_SESSION['core_user_at'] < 300) {
        return $_SESSION['core_user'];
    }
    $ch = curl_init('https://databasehub.sorai.nl/api/me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Referer: https://{APP_SLUG}.sorai.nl',
            'Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? ''),
        ],
    ]);
    $res = curl_exec($ch);
    $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    $user = $ok ? json_decode($res, true) : null;
    $_SESSION['core_user'] = $user;
    $_SESSION['core_user_at'] = time();
    return $user;
}

$user = coreUser();
if (! $user) {
    header('Location: https://databasehub.sorai.nl/login');
    exit;
}
// $user = ['id'=>..,'name'=>..,'email'=>..,'is_super_admin'=>..,...]
```

Voorwaarden:
- App draait op https://{APP_SLUG}.sorai.nl (cookie-domein .sorai.nl)
- `{APP_SLUG}.sorai.nl` staat in `SANCTUM_STATEFUL_DOMAINS` in de CORE
  server-.env (vraag de CORE-beheerder / doe dit in de CORE-repo)
- Bij uitloggen: eigen PHP-sessie wissen + linken naar CORE-logout
- Rollen/toegang: lokaal opslaan (eigen tabellen, bv. in SQLite) met
  de CORE `users.id` of e-mail als sleutel — zie autorisatie-sectie
- Data (klanten/materieel/personeel) ophalen via de data-API met
  hetzelfde cookie-relay (zelfde headers, andere endpoints)
