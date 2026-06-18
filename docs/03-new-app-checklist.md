# Nieuwe child-app aanmaken — checklist

> Volg deze stappen om veilig een nieuwe app (bv. Fleet, Sales, Schade)
> te koppelen aan Boels CORE.

## TL;DR voor SSO werkend te krijgen

- Zelfde `APP_KEY` als Boels CORE in de child-app `.env` (anders kan child-app de session-cookie niet decrypteren)
- `SESSION_DOMAIN=.sorai.nl` in beide `.env` bestanden
- Zelfde `SESSION_COOKIE` naam — Laravel default is `Str::slug(APP_NAME).'_session'`, dus óf identieke `APP_NAME` óf expliciet `SESSION_COOKIE=boels_core_platform_session` in beide
- Subdomein onder `*.sorai.nl` met HTTPS
- In de child-app: als `! Auth::check()` → redirect naar `https://databasehub.sorai.nl/login`

---

## 1. Antagonist hosting setup (eenmalig per app)

In DirectAdmin:

1. **Subdomein aanmaken**
   - Subdomein → bv. `fleet.sorai.nl`
   - Map die DirectAdmin aanmaakt: `/domains/fleet.sorai.nl/`
2. **Naast `public_html/` map `laravel_app/` aanmaken** (via FileZilla)

## 2. MySQL user aanmaken voor de app

Op je lokale Boels CORE map of via SSH op de server:

```bash
php artisan boels:create-app-user fleet
```

Output: GRANT-statements + DirectAdmin instructies + `.env` regels.

Volg de output:
1. DirectAdmin → MySQL Management → New User
2. phpMyAdmin → run de geprinte GRANT-statements
3. Plak de `.env` regels in de nieuwe app

## 3. GitHub repository

1. Nieuwe repo aanmaken op GitHub, bv. `fleet_app_boels`
2. Lokaal: `~/Desktop/fleet_app_Boels/` map aanmaken
3. Repo daaraan koppelen

## 4. Secrets in GitHub Actions

Op github.com → repo → Settings → Secrets:

| Secret | Waarde |
|---|---|
| `FTP_SERVER` | (zelfde als bij Boels CORE) |
| `FTP_USERNAME` | (zelfde) |
| `FTP_PASSWORD` | (zelfde) |

## 5. CLAUDE.md in de nieuwe app

Kopieer `docs/CHILD_APP_CLAUDE_MD_TEMPLATE.md` uit Boels CORE.
Plaats als `CLAUDE.md` in de root van de nieuwe app-map.
Vervang `{APP_NAAM}` en `{APP_SLUG}` overal.

## 6. Open Claude in de nieuwe map

Vertel Claude bv.:

> Bouw de Fleet App. Volg CLAUDE.md strikt. Gebruik dezelfde MySQL
> database als Boels CORE. Hosting via FTPS naar Antagonist op
> /domains/fleet.sorai.nl/. Volg dezelfde deploy-strategie als Boels CORE
> (zip + unpack PHP-script).

## 7. App registreren in Boels CORE

Login op databasehub.sorai.nl → Beheer → Applicaties → toevoegen.
(Of doe dit automatisch via stap 2 — die voegt hem al toe.)

## 8. Permissies aanmaken

Beheer → Permissies → nieuwe permissies voor deze app:
- `fleet.view` — mag de app zien in launcher
- `fleet.manage` — mag in de app beheren

Toewijzen aan rollen via Beheer → Rollen.

## 9. Tabel-eigendom registreren

Voor elke nieuwe tabel die de app aanmaakt: zorg dat hij in
`app_table_ownership` komt te staan met `owner_slug = 'fleet'`.

---

## Wat is afgedwongen vs. afgesproken

| Maatregel | Type | Te omzeilen? |
|---|---|---|
| MySQL GRANT-rechten | **Hard** | Nee, MySQL weigert command |
| Tabel-prefix `fleet_` | Afspraak | Ja, maar zichtbaar bij review |
| CLAUDE.md regels | Afspraak | Alleen door instructies te negeren |
| Dagelijkse backup | **Hard** | Nee, draait via scheduler |

De combinatie maakt het praktisch onmogelijk om per ongeluk Boels CORE
te beschadigen.
