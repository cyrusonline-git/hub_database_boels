# Boels CORE (hub_database_Boels) — notities voor Claude

Laravel-app op databasehub.sorai.nl (MySQL). Deploy: `git push origin main` → GitHub Action bouwt een
release → `__pull_deploy.php?k=&tag=` → `__migrate.php?k=` (Claude doet dit zelf).

## Uploads SAP (12-11-2026)
De vier SAP-exports (WOW, factuurrun, 8.7 planning-View, 2.6 reserveringen) landen centraal in CORE:
Beheer → Uploads SAP (`Admin/SapUploadController`), importers in `app/Services/Sap/*`, config
`config/sap.php`, API `/api/sap/*`, `/api/hire/*`, `/api/planning/*`, `/api/acties`. Regels per
bestand en de SAP-code→medewerker-logica staan in `docs/sap-uploads.md`. Scriptupload:
`public/__sap_upload.php?k=<DEPLOY_SECRET>`. Lokaal testen: `.env` met SQLite (gitignored),
`php artisan migrate`, `php artisan sap:import <pad>`; PHP 8.5 lokaal vereist
`composer install --ignore-platform-req=php` (server draait 8.4). Nooit klantdata of .env committen.
