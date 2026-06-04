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

## Identity Provider

Deze app authenticeert tegen Boels CORE. Twee mogelijke setups:

### Aanpak A (huidige standaard) — gedeelde sessies via DB
- App heeft eigen `LoginController` die `users` tabel checkt
- Sessies in `sessions` tabel (gedeeld met CORE)
- Permissies uit `permissions` + `role_permissions`

### Aanpak B (toekomst) — Single Sign-On via API token
- Gebruiker logt in op CORE → krijgt token → wordt doorgestuurd naar app
- App valideert token via `GET https://databasehub.sorai.nl/api/me`
  met `Authorization: Bearer {token}`

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
