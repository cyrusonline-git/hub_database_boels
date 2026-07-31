# Migratie-prompt: bestaande app koppelen aan Boels CORE (hybride SSO)

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
De login moet werken zoals in de Scanner App (referentie-implementatie:
/Users/Wim/Desktop/scanner/v2/ — bestanden includes/auth.php, config.php,
login.php, geen-toegang.php, admin/toegang.php, database.php).
Het architectuur-sjabloon staat lokaal in
/Users/Wim/Desktop/hub_database_Boels/docs/CHILD_APP_CLAUDE_MD_TEMPLATE.md;
vul het in en zet het als CLAUDE.md in de projectroot, aangepast aan dit model.

## KEIHARDE RANDVOORWAARDE: deze app is LIVE met echte gebruikers en data

- STAP 0, vóór al het andere — bescherm de live database:
  a. Controleer de GitHub deploy-workflow: databasebestanden (*.db, *.sqlite,
     *-wal, *-shm), data/-mappen en backups/ MOETEN uitgesloten zijn van de
     FTP-deploy én in .gitignore staan. Zo niet: eerst fixen. Een deploy die
     een (lege, lokale) database meestuurt overschrijft de live data — dit is
     het grootste risico van het hele traject.
  b. Ontbreekt een nachtelijke database-backup? Kopieer het workflow-patroon
     van INHUUR (boels_inhuur/.github/workflows/backup-database.yml: FTPS-
     download van de live db naar GitHub Artifact, 30 dagen bewaard).
  c. Vraag Wim om een verse kopie van de LIVE database (download van de
     server) en test alle migraties dáárop — de lokale data-map is leeg en
     bewijst niets.
- Elk migratiescript dat op de server draait: maakt ZELF eerst een timestamped
  kopie van het databasebestand, heeft een dry-run-modus (?dry=1) die alleen
  toont wat er zou gebeuren, is idempotent (2× draaien = zelfde resultaat),
  is beveiligd met een geheime sleutel in de URL, en verwijdert zichzelf na
  geslaagde run. Ruim bij deze klus ook oude onbeveiligde hulpscripts op
  (add-*.php, fix-*.php, test-*.php) die nu op live staan.
- ALLE bestaande data blijft intact. GEEN tabellen droppen die data bevatten.
- ALLE bestaande gebruikers (klant én Boels) blijven bestaan en kunnen direct
  na de omzetting inloggen met hun huidige wachtwoord. Niemand wordt opnieuw
  aangemaakt, niemand krijgt een nieuw wachtwoord. Log nooit wachtwoorden of
  hashes, ook niet in debug-uitvoer.
- Bouw en test eerst in een testversie/submap naast de live app (zoals
  scanner.sorai.nl/v2/) en zet pas door naar live na akkoord van Wim.
- Ontwerp de omschakeling als ÉÉN punt (bv. één include of één constante in
  config.php) zodat rollback = één bestand terugzetten. Laat de oude
  login-bestanden onaangeroerd staan tot Wim akkoord geeft op verwijderen.

## Het model

CORE levert identiteit (wie ben je) én — sinds de rollen-per-app-functie —
de functionele rollen van Boels-medewerkers voor deze app. Die rollen beheert
Wim centraal in CORE (Beheer → Applicaties → deze app → "Rollen in deze
app"); de app vraagt ze op via GET /api/access/{APP_SLUG} (zelfde
cookie-relay; antwoord: roles[{slug,name,scope}], is_super_admin) en mapt de
rol-slugs op eigen gedrag. Publiceer daarom een endpoint core-roles.php in
de webroot van de app dat de rollen als JSON teruggeeft:
{"app":"{APP_SLUG}","name":"{APP_NAAM}","roles":[{"slug":..,"name":..,"description":..}]}
(read-only, alleen rolnamen — geen gebruikers). CORE heeft een knop
"Importeer rollen uit de app" die dit endpoint uitleest; Wim hoeft dan niets
over te typen. Referentie: /Users/Wim/Desktop/scanner/v2/core-roles.php.
Publiceer daarnaast core-users.php: zelfde webroot, geeft
{"users":[{"email":..,"roles":["slug",..]}]} terug met ALLEEN de
Boels-medewerkers (NOOIT klant-accounts). Beveiliging ZONDER sleutel:
sta alleen verzoeken toe vanaf de eigen server (CORE draait op dezelfde
host) — bovenaan het bestand:
  $ok = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', $_SERVER['SERVER_ADDR'] ?? '']);
  if (! $ok) { http_response_code(403); exit('forbidden'); }
CORE haalt dit endpoint automatisch op bij het koppelen van de app en
koppelt de rollen aan bestaande CORE-logins op e-mailadres — geen
sync-sleutel of handwerk nodig.
Klant-accounts en klant-rollen blijven volledig lokaal.

1. Boels-medewerkers — inloggen via CORE (launcher) dankzij het cookie-relay
   patroon: stuur de Cookie-header van de bezoeker server-side met curl naar
   GET https://databasehub.sorai.nl/api/me (headers: Accept:
   application/json, Referer: https://{APP_SLUG}.sorai.nl, Cookie:
   bezoekerscookies), cache het antwoord 5 minuten in de eigen PHP-sessie.
   Géén APP_KEY, géén tokens, géén wachtwoordcontrole tegen CORE.
2. Klant-medewerkers (staan niet in CORE) — lokale accounts in de app met
   password_hash/password_verify, beheerd door een app-beheerder. Het eigen
   inlogscherm controleert ALLEEN lokale accounts en heeft een knop
   "Inloggen als Boels-medewerker" → https://databasehub.sorai.nl/login.
   Lokaal gaat altijd vóór.

## Migratie van bestaande gebruikers (het hart van deze opdracht)

Nieuwe tabellen (prefix {APP_SLUG}_):
- {APP_SLUG}_roles (id, name, slug UNIQUE, description) — seed idempotent
  met de bestaande rollen van deze app + superadmin.
- {APP_SLUG}_accounts (id PK, type CHECK IN ('core','lokaal'), core_user_id
  UNIQUE nullable, email COLLATE NOCASE, username (als de oude app
  gebruikersnamen had), name, password_hash nullable, active DEFAULT 1,
  last_seen, created_at) + partial unique index op email WHERE type='lokaal'.
- {APP_SLUG}_user_roles (account_id PK, role_id).

De eenmalige, idempotente migratie (met guard, draait automatisch mee):
1. Kopieer ALLE bestaande gebruikers naar {APP_SLUG}_accounts met exact
   dezelfde id (expliciete id-insert), type 'lokaal', en neem e-mail,
   gebruikersnaam, naam én de bestaande wachtwoord-hash 1-op-1 over. Door het
   gelijk houden van de id's blijven alle verwijzingen in de app-data
   (created_by, sent_by, enz.) gewoon kloppen — verander daar niets aan.
2. Zet hun huidige rol automatisch om naar {APP_SLUG}_user_roles.
3. Laat de oude gebruikerstabel staan (alleen niet meer als loginbron);
   droppen mag pas veel later, na akkoord.
4. Gebruikte de oude app password_hash()? Dan werkt inloggen direct via
   password_verify(). Een ouder formaat (md5/plaintext)? Ondersteun dat
   formaat bij de controle en herhash naar password_hash() bij de eerste
   geslaagde login.
5. Had de oude app gebruikersnamen i.p.v. e-mail? Maak het inlogveld
   "E-mailadres of gebruikersnaam" en match op beide.
6. Controleer na migratie met een telling: aantal accounts vóór = aantal
   accounts ná, en rapporteer dat expliciet.

Automatische koppeling van Boels-medewerkers (geen handwerk!): logt iemand
voor het eerst via CORE in (/api/me geeft id + e-mail), kijk dan éérst of er
al een lokaal account bestaat met datzelfde e-mailadres. Zo ja: upgrade dát
account (zet type='core', vul core_user_id, wachtwoord-hash mag blijven
staan als vangnet) — zelfde id, dus alle data blijft van hem. Voor
'core'-accounts is de rol uit /api/access/{APP_SLUG} leidend (sync de
CORE-rolslug bij elke rolverversing naar {APP_SLUG}_user_roles); voor
'lokaal'-accounts blijft de lokale rol leidend. Zo nee: maak een nieuw
'core'-account aan; heeft hij ook geen CORE-rol voor deze app, dan wacht
hij op toegang. Tot het
moment van die eerste CORE-login blijft de Boels-medewerker gewoon inloggen
zoals hij nu doet, met zijn bestaande app-wachtwoord. De overstap naar CORE
kan dus geleidelijk.

## Twee beheerniveaus

- superadmin (Boels) — volledig beheer: app-instellingen (e-mail, imports,
  stamdata) én toegangsbeheer over álle accounts. CORE-gebruikers met
  is_super_admin krijgen deze rol automatisch. Bestaande Boels-beheerders in
  de app: geef hun account bij de migratie de superadmin-rol (in overleg met
  Wim welke dat zijn — vraag het als het niet duidelijk is).
- admin (klant-beheerder) — alleen lokale gebruikers aanmaken/rollen
  geven/wachtwoord resetten/deactiveren + de gewone overzichten. GEEN
  app-instellingen, GEEN zicht op Boels/'core'-accounts.
- Klant-schermen: neutrale teksten (geen "Boels", "CORE" of "klant"), geen
  Boels-accounts in de lijst. Afscherming óók server-side; een klant-admin
  kan nooit de superadmin-rol toekennen.

## De flow (voorkom redirect-lussen)

requireLogin() op elke beveiligde pagina en elk API-endpoint:
1. Lokale sessie? → elke 5 min account + rol herladen (weg/inactief → sessie
   wissen → login.php; geen rol → geen-toegang.php).
2. Anders CORE-relay: gevonden → e-mail-match/upsert zoals hierboven;
   is_super_admin → superadmin; geen rol → geen-toegang.php ("Wacht op
   toegang" in het toegangsbeheer).
3. Anders → eigen login.php (NIET naar CORE; die knop staat op login.php).

login.php en geen-toegang.php roepen nooit requireLogin aan. login.php
probeert bij GET eerst stil de CORE-relay. geen-toegang.php heeft "Opnieuw
controleren" (rol geforceerd herladen) + uitlogknop. logout.php wist de eigen
sessie; alleen voor CORE-gebruikers een knop "Volledig uitloggen bij Boels
CORE". Wachtwoord vergeten: Boels → databasehub.sorai.nl/wachtwoord-vergeten;
lokaal → "vraag je beheerder" (nooit zelf een reset-flow bouwen).
Sessie: $_SESSION['user_id'] = altijd het eigen account-id, plus auth_type
('core'|'lokaal'); unieke sessienaam per app (bv. {APP_SLUG}_session) en
cookie-instellingen httponly + secure + samesite=lax; het sessiecookie van
de app zelf NIET op domein .sorai.nl zetten (alleen het eigen subdomein),
anders botst hij met de CORE-cookie.

## Beveiliging inlogscherm (verplicht)

Generieke foutmelding voor alle faalgevallen; 5 fouten → 60 s slot +
usleep(250ms) per fout; session_regenerate_id(true) na login; CSRF-token op
login- en beheer-POSTs; wachtwoorden min. 8 tekens; eigen account niet
kunnen deactiveren/intrekken/resetten.

## Bekende valkuilen (al eerder getackeld)

1. SSO werkt niet / blijven hangen op CORE-login → {APP_SLUG}.sorai.nl mist
   in SANCTUM_STATEFUL_DOMAINS van CORE (CORE-kant regelen door Wim/Claude
   in de CORE-repo — niet omheen bouwen). App moet op HTTPS onder *.sorai.nl
   draaien.
2. Launcher-tegel in CORE (Beheer → Applicaties) naar de juiste URL laten
   wijzen (testfase: naar de testmap).
3. Grep op verwijzingen naar het oude loginscherm en oud gebruikersbeheer;
   oud wachtwoordbeheer uitschakelen (doorverwijzen naar het nieuwe
   toegangsbeheer).
4. JOINt de app op de oude gebruikerstabel voor naamweergave? Laat die JOINs
   werken door de tabel als naamweergave-cache bij te houden, of laat ze
   staan — de id's zijn immers gelijk gebleven.
5. /api/me kan het antwoord in een "data"-envelop verpakken — uitpakken.
6. /api/me-antwoord 5 minuten cachen (ook negatief, tegen hammering van
   CORE bij uitgelogde bezoekers).
7. Rol ingetrokken / account gedeactiveerd → binnen max. 5 min effect.
8. E-mail-match alleen op exact adres (case-insensitief); bestaat hetzelfde
   adres meerdere keren lokaal, koppel dan niet automatisch maar meld het.
9. SQLite-apps: gebruik één PDO-verbinding met busy_timeout; kopieer bij
   backup óók de -wal/-shm bestanden of doe eerst PRAGMA wal_checkpoint.

## Oplevering

Test lokaal minimaal (op de kopie van de LIVE database): migratie idempotent
(2× draaien, tellingen gelijk), bestaand wachtwoord werkt nog, e-mail-match-
upgrade, rol toekennen/intrekken, deactiveren, dry-run toont juiste plan.
Vertel na afloop: welke bestanden gewijzigd zijn, het browser-testplan stap
voor stap (met als eerste test: een bestáánde gebruiker logt in met zijn
oude wachtwoord!), het rollback-plan in max. 3 regels (welk bestand
terugzetten + welke backup terugplaatsen), en wat er aan de CORE-kant nog
moet gebeuren. Ik ben geen developer — leg het eenvoudig uit.
