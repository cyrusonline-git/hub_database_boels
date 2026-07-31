# Migratie-prompt V2: bestaande app koppelen aan Boels CORE (hybride SSO)

> Versie 2 — 31-07-2026, opgesteld na de volledige migratie van de
> offertes-app (offertes.sorai.nl). Bevat alle lessen uit dat traject.
>
> Kopieer alles vanaf "# Opdracht" hieronder en plak het in de Claude-sessie
> van de app die je wilt ombouwen. Vervang eerst overal {APP_NAAM} en
> {APP_SLUG} (bv. "Tankapp" en "tankapp").
>
> Vooraf regelen met Claude in de CORE-repo: subdomein toevoegen aan
> SANCTUM_STATEFUL_DOMAINS + app-tegel aanmaken in Beheer → Applicaties.

---

# Opdracht: login via Boels CORE (hybride SSO-model) — MET BEHOUD VAN ALLE
# BESTAANDE GEBRUIKERS EN DATA

Deze applicatie — {APP_NAAM}, draaiend op https://{APP_SLUG}.sorai.nl — wordt
een child-app van Boels CORE Platform (https://databasehub.sorai.nl).

**Referentie-implementatie: de offertes-app, /Users/Wim/Desktop/accountmanagers**
(bestanden: includes/auth.php, includes/sso-migratie.php, config.php,
login.php + login-hybride.php, geen-toegang.php, admin/toegang.php,
core-roles.php, core-users.php, CLAUDE.md). Die is nieuwer dan de
Scanner-referentie — volg de offertes-app. Vul ook het CLAUDE.md-sjabloon
(docs/CHILD_APP_CLAUDE_MD_TEMPLATE.md in de CORE-repo) in en zet het als
CLAUDE.md in de projectroot.

## LES NUL (de belangrijkste, uit de praktijk)

1. **Lokale login mag NOOIT vervallen.** Er zijn gebruikers die niet bij
   Boels werken en nooit een CORE-login krijgen. Het eigen inlogscherm
   (e-mailadres/gebruikersnaam + wachtwoord) blijft permanent bestaan.
   CORE is een extra ingang, geen vervanging. Stel nooit voor om lokale
   login uit te faseren.
2. **Twee schakelaars in config.php**, allebei apart:
   - `AUTH_HYBRIDE` (false/true) — het hele nieuwe model aan/uit.
     Live gaan = deze op true. Rollback = terug op false. Оude
     loginbestanden laten staan tot Wim akkoord geeft op verwijderen.
   - `CORE_LOGIN_ACTIEF` (standaard **false**!) — de CORE-ingang zelf:
     de knop "Inloggen als Boels-medewerker" én de stille CORE-herkenning.
     Zolang CORE niet volledig af is, blijft deze op false — anders klikt
     iemand per ongeluk op de CORE-knop, wordt zijn account geüpgraded
     naar type 'core' en kan hij daarna niet meer met zijn oude wachtwoord
     inloggen. Pas op true als Wim zegt dat CORE klaar is.
3. **Controleer de ACTUELE CORE-conventies vóór het bouwen.** De afspraken
   (core-users-beveiliging, rollen-model) zijn tijdens het offertes-traject
   meermaals gewijzigd. Lees vóór het bouwen de huidige stand in de
   CORE-repo: docs/CHILD_APP_MIGRATIE_PROMPT.md én de echte code in
   app/Http/Controllers/Admin/ApplicationController.php (importUsers /
   fetchEndpoint) — de code is de waarheid, niet de docs.

## STAP 0 — bescherm de live database (vóór al het andere)

a. Deploy-workflow (.github/workflows/ftp-deploy.yml) en .gitignore:
   databasebestanden (*.db, *.sqlite, *-wal, *-shm), database-/data-mappen,
   uploads en backups/ MOETEN uitgesloten zijn. Let op: patronen als
   `database/**` gelden alleen voor de root — voeg óók `**/database/**` en
   `**/uploads/...` toe, anders lekt een testmap (v2/) zijn database mee.
   Een deploy die een lege lokale database meestuurt overschrijft de live
   data — grootste risico van het hele traject.
b. Nachtelijke backup-workflow toevoegen (patroon:
   accountmanagers/.github/workflows/backup-database.yml — FTPS-download
   naar GitHub Artifact, 30 dagen). LET OP: controleer de GitHub
   artifact-opslagquota; als die vol zit (was zo door oude INHUUR-backups)
   faalt de upload met "Artifact storage quota has been hit" — oude
   artifacts opruimen via de API. Na opruimen duurt het 6–12 uur voordat
   GitHub de quota herberekent.
c. Vraag Wim om een verse kopie van de LIVE database en test alle
   migraties dáárop. De lokale kopie kan verouderd zijn (offertes: lokaal
   13 gebruikers, live 19).

## Het model

CORE levert identiteit (/api/me) én de functionele rollen van
Boels-medewerkers (/api/access/{APP_SLUG}); beide via het cookie-relay
patroon: stuur de Cookie-header van de bezoeker server-side met curl naar
CORE (headers: Accept: application/json, Referer:
https://{APP_SLUG}.sorai.nl, Cookie: bezoekerscookies), cache het antwoord
5 minuten in de eigen PHP-sessie (ook negatief, tegen hammering). Géén
APP_KEY, géén tokens. Guard: `function_exists('curl_init')`, anders null
teruggeven zodat de app blijft werken.

1. **Boels-medewerkers** (pas relevant zodra CORE_LOGIN_ACTIEF aan staat):
   inloggen via CORE. Rollen: CORE is ABSOLUUT leidend voor accounts van
   type 'core' — SPIEGEL bij elke 5-minuten-rolverversing het antwoord van
   /api/access/{APP_SLUG} volledig naar {APP_SLUG}_user_roles (dus ook
   rollen verwijderen die in CORE zijn uitgevinkt; meerdere rollen
   mogelijk → tabel met samengestelde sleutel (account_id, role_id)).
   Alleen als de CORE-relay faalt: de laatst gespiegelde kopie gebruiken
   (anders sluit een netwerkstoring iedereen buiten).
   is_super_admin → rol superadmin.
2. **Lokale accounts** (klanten/externen én Boels-collega's die nog niet
   via CORE gaan): password_hash/password_verify in de app. Inlogveld
   "E-mailadres of gebruikersnaam" (match op beide, hoofdletterongevoelig;
   bij dubbel e-mailadres alleen op gebruikersnaam). Lokaal gaat vóór.
   Rollen van lokale accounts blijven volledig lokaal beheerd.
3. **Meerdere rollen** → na binnenkomst keuzescherm kies-rol.php ("Als
   welke rol wil je inloggen?", sessie-breed, "Wissel rol" in het menu,
   alle checks op de actieve rol). Eén rol → direct binnen.

## Migratie van bestaande gebruikers

Tabellen (prefix {APP_SLUG}_): _roles (id, name, slug UNIQUE, description),
_accounts (id PK behouden = oude users.id!, type CHECK ('core','lokaal'),
core_user_id UNIQUE nullable, email COLLATE NOCASE, username, name,
password_hash, evt. app-specifieke velden zoals area_id/depot_id, active,
last_seen, created_at; partial unique indexes op email/username WHERE
type='lokaal' — maar sla de e-mailindex over als er nu al dubbele adressen
zijn, anders loopt de migratie stuk), _user_roles (account_id, role_id,
PRIMARY KEY (account_id, role_id)).

De migratie zelf (zie includes/sso-migratie.php van de offertes-app):
- Draait automatisch mee via een guard (tabel bestaat niet / leeg terwijl
  users gevuld is) én is los aan te roepen via een beveiligd script
  migratie-check.php?k=<random>&dry=1 (dry-run toont alleen het plan).
- Maakt vóór de echte run ZELF een timestamped kopie van het
  databasebestand in database/backups/ (met wal_checkpoint / -wal en -shm).
- Kopieert ALLE gebruikers 1-op-1: zelfde id, type 'lokaal', e-mail,
  gebruikersnaam, naam, wachtwoord-hash ongewijzigd. Alle verwijzingen
  (created_by enz.) blijven daardoor kloppen.
- Bestaande beheerders → rol superadmin (in overleg met Wim).
- Idempotent (INSERT OR IGNORE), telling vóór = telling ná, expliciet
  rapporteren. Nooit wachtwoorden of hashes loggen.
- Oude users-tabel blijft staan als naamweergave-cache voor JOINs
  (bijhouden bij accountwijzigingen; wachtwoordveld leeg laten).
- **Opruimen van het migratiescript: verwijder het uit de REPO en push**
  — de FTP-deploy verwijdert het dan van de server. Alleen server-side
  verwijderen werkt niet: de volgende deploy zet het gewoon terug.
- Ruim ook oude onbeveiligde hulpscripts op (setup-*, reset-*, migrate-*,
  test-*, fix-*.php) — via de repo, zelfde reden.

E-mail-match bij eerste CORE-login: bestaat er precies één lokaal account
met dat adres → dát account upgraden (type='core', core_user_id invullen,
hash laten staan, zelfde id). Meerdere matches → NIET koppelen, loggen.

## Twee beheerniveaus + toegangsbeheer

- superadmin (Boels): alles, incl. app-instellingen. Maak een
  requireSuperadmin() naast requireRole(); superadmin passeert élke
  requireRole-check. Instellingen-pagina's op requireSuperadmin() zetten.
- admin (beperkt): alleen lokale accounts beheren + gewone overzichten,
  geen instellingen, geen zicht op core-accounts, kan nooit superadmin
  toekennen (server-side afdwingen).
- admin/toegang.php: core-accounts alleen-lezen ("beheerd in CORE") —
  behálve app-specifieke velden die CORE niet kent (bv. area/depot).
  Wachtwoordreset/deactiveren alleen voor lokale accounts; eigen account
  nooit kunnen deactiveren/resetten; CSRF op alle POSTs.
- Oud gebruikersbeheer (admin/users.php) laten staan, maar in hybride
  modus doorverwijzen naar toegang.php.

## Sync-endpoints voor CORE

- core-roles.php (webroot, openbaar, read-only): alle rollen als JSON
  {"app","name","roles":[{slug,name,description}]}. Met vaste lijst als
  vangnet zolang de migratie lokaal nog niet gedraaid heeft. CORE-knop
  "Importeer rollen uit de app" leest dit uit.
- core-users.php (webroot): {"users":[{"email","roles":["slug",..]}]} met
  ALLEEN Boels-medewerkers, nooit klant-accounts, nooit namen/hashes.
  Beveiliging: CONTROLEER DE ACTUELE CONVENTIE in de CORE-code — per
  31-07-2026 is dat een server-IP-check (alleen 127.0.0.1/::1/eigen
  SERVER_ADDR, CORE draait op dezelfde host), zonder sleutel.

## De flow (voorkom redirect-lussen)

requireLogin() op elke beveiligde pagina/endpoint:
1. Lokale sessie → elke 5 min verversen (weg/inactief → login.php; wel
   rollen maar geen actieve → kies-rol.php; geen rollen → geen-toegang.php).
2. Anders, ALLEEN als CORE_LOGIN_ACTIEF: CORE-relay → upsert/spiegelen →
   zelfde rolafhandeling.
3. Anders → login.php.
login.php, geen-toegang.php en kies-rol.php roepen nooit requireLogin aan.
Gebruik voor alle redirects een ABSOLUUT webpad afgeleid van de app-root
(zie appWebRoot() in de offertes-config): relatieve redirects zoals
'index.php' wijzen vanuit /admin/ naar het verkeerde bestand, en een
testmap (/v2/) wijst anders naar de live root.
logout.php: eigen sessie wissen; alleen voor core-gebruikers een pagina
met knop "Volledig uitloggen bij Boels CORE". Wachtwoord vergeten: lokaal
"vraag je beheerder"; CORE-verwijzing alleen tonen als CORE_LOGIN_ACTIEF.
Sessie: unieke sessienaam per app ({APP_SLUG}_session; testversie een
andere), cookie httponly+secure+samesite=lax, NIET op .sorai.nl.
LET OP: de nieuwe sessienaam logt iedereen één keer uit — meld dat aan Wim.

## Beveiliging inlogscherm (verplicht)

Generieke foutmelding; 5 fouten → 60 s slot + usleep(250ms);
session_regenerate_id(true) na login; CSRF; wachtwoorden min. 8 tekens.

## Testversie (v2/) — leerpunten

- Volledige kopie in v2/ met eigen config: AUTH_HYBRIDE=true,
  CORE_LOGIN_ACTIEF naar wens, TEST_MODE=true (e-mails alleen loggen —
  bouw die guard in de mailhelper!), eigen sessienaam, TESTVERSIE-banner.
- Haal de default-admin-seed (admin/admin123) uit v2/db.php — een lege
  testdatabase op een publieke URL mag geen bekend wachtwoord krijgen.
- De v2-database is LEEG tot er een kopie van de live database in
  v2/database/ wordt gezet (via FTP). Meld dat expliciet — "ik zie 0
  gebruikers" in de testversie betekent níét dat live data weg is.
- CORE-tegel in de testfase naar /v2/ laten wijzen; bij livegang terug.
- Na afronden: v2 volledig uit de repo verwijderen; oude /v2/-links vallen
  dan via ErrorDocument op het live inlogscherm. Restjes die de app zelf
  op de server aanmaakte (v2/database/) blijven op de FTP staan — melden.

## Livegang (volgorde die goed werkte)

1. Dry-run op de LIVE database via migratie-check.php?k=..&dry=1 (leest
   alleen; controleer aantallen en dubbele e-mailadressen).
2. Verse backup draaien (workflow handmatig triggeren).
3. AUTH_HYBRIDE=true committen + pushen; wachten op de deploy-workflow.
   (FTP-deploy kan falen met ETIMEDOUT — paar minuten wachten en de
   workflow opnieuw starten lost het meestal op.)
4. Migratie gecontroleerd uitvoeren via migratie-check.php (echte run),
   rapport controleren (telling vóór = ná, backupnaam noteren).
5. Live verifiëren: nieuw inlogscherm, redirects, core-roles.php.
6. Migratiescript uit de repo verwijderen + pushen.
7. Bewijs leveren met een tijdelijk beveiligd telscript (aantallen users/
   accounts/rollen/data) en dat script daarna óók weer via de repo
   verwijderen.

## Oplevering

Test lokaal minimaal (op de kopie van de LIVE database): migratie
idempotent (2× draaien, tellingen gelijk), bestaand wachtwoord werkt nog
(PHP built-in server + curl met CSRF-flow werkt goed als testopstelling),
e-mail-match-upgrade, rol toekennen/intrekken, deactiveren, gebruiker
zonder rol → geen-toegang, beperkte rol kan niet bij instellingen,
dry-run toont juiste plan. Vertel na afloop: welke bestanden gewijzigd
zijn, het browser-testplan stap voor stap (eerste test: een bestáánde
gebruiker logt in met zijn oude wachtwoord!), het rollback-plan in max.
3 regels, en wat er aan de CORE-kant nog moet gebeuren. Wim is geen
developer — leg alles eenvoudig uit, en lever bewijs met tellingen in
plaats van geruststellingen.
