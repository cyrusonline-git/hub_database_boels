# Prompt: een app volledig omzetten naar Boels CORE

> Plak dit in een Claude-sessie in de CORE-map
> (`/Users/Wim/Desktop/hub_database_Boels`). Vervang [APP-NAAM],
> [APP-MAP] en [SUBDOMEIN].

---

```
Zet de app [APP-NAAM] volledig om naar het Boels CORE-platform (SSO-login +
rollen uit CORE). Doe alles zelf, aan beide kanten — je hebt mijn toestemming
om te committen, pushen en deployen.

WAAR ALLES STAAT
- CORE: deze map (/Users/Wim/Desktop/hub_database_Boels), live op
  https://databasehub.sorai.nl. Deploy: push naar main → GitHub Action bouwt
  release → __pull_deploy.php aanroepen met de DEPLOY_SECRET (staat in je
  geheugen onder "deploy-flow"; anders in de server-.env van CORE).
- De om te zetten app: [APP-MAP, bv. /Users/Wim/Desktop/Tankapp],
  live op https://[SUBDOMEIN].sorai.nl. Eigen git-repo met FTP-deploy-Action.
- REFERENTIE (werkend en door mij goedgekeurd): de offerte-app in
  /Users/Wim/Desktop/accountmanagers. Kopieer dat model: login-hybride.php,
  kies-rol.php, geen-toegang.php, includes/auth.php + includes/sso-migratie.php,
  core-roles.php, core-users.php, admin/toegang.php. Pas tabelnamen/slug aan.
- Eisen en valkuilen: docs/CHILD_APP_MIGRATIE_PROMPT.md en
  docs/CHILD_APP_CLAUDE_MD_TEMPLATE.md in deze map. Strikt volgen.

HET MODEL (niet van afwijken)
- Boels-medewerkers loggen in via CORE (cookie-relay /api/me). Hun rollen
  komen ABSOLUUT LEIDEND uit /api/access/[slug]: spiegelen bij elke
  5-min-verversing, inclusief verwijderen wat in CORE is uitgevinkt. Een
  lokaal inactief-vinkje blokkeert een core-account niet. Bij meerdere
  rollen: keuzescherm "Als welke rol wil je inloggen?" + "Wissel rol".
  CORE-super-admins mogen altijd alles.
- Bestaande gebruikers 1-op-1 migreren met DEZELFDE id's (verwijzingen in de
  data blijven kloppen); wachtwoord-hashes overnemen; e-mail-match-upgrade
  bij eerste CORE-login. Niemand raakt buitengesloten, niemand een nieuw
  wachtwoord.
- Klant-accounts (als de app die heeft): volledig lokaal, eigen
  toegangsbeheer, CORE komt er niet aan.
- Geen eigen wachtwoordschermen voor Boels-medewerkers; wachtwoord vergeten
  → https://databasehub.sorai.nl/wachtwoord-vergeten.

DATABESCHERMING (eerst, vóór al het andere)
- Check dat de deploy-workflow databasebestanden en data-mappen UITSLUIT.
- Nachtelijke db-backup-workflow toevoegen als die ontbreekt (patroon:
  INHUUR boels_inhuur/.github/workflows/backup-database.yml).
- Migratiescripts: eigen backup vooraf, dry-run, idempotent, beveiligd,
  zichzelf opruimend. Bouw eerst in een v2/testmap naast live; pas naar
  live na mijn akkoord. Rollback = één schakelpunt (CORE_LOGIN_ACTIEF).

CORE-KANT (hoort er ook bij)
1. [SUBDOMEIN].sorai.nl toevoegen aan SANCTUM_STATEFUL_DOMAINS via een
   eenmalig script beveiligd met de DEPLOY_SECRET (patroon: eerdere
   __env-*.php commits in de git-historie van deze repo).
2. Als de app core-roles.php/core-users.php publiceert: ik koppel hem daarna
   zelf via Beheer → Applicaties → "Koppel via URL" (tegel + rollen +
   gebruikers komen dan automatisch mee).

OPLEVERING
Rapporteer eenvoudig (ik ben geen developer): wat er is gebouwd, een
browser-testplan stap voor stap (eerste test: bestaande gebruiker logt in
met zijn oude wachtwoord!), het rollback-plan in 3 regels, en wat ik zelf
nog moet doen.
```
