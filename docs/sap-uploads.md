# Uploads SAP (Boels Industrial) — bestanden, regels en API

Vier SAP-rapporten voeden het Industrial-dashboard (dashboard.sorai.nl) en zijn via de CORE-API
beschikbaar voor andere apps. Upload: **Beheer → Uploads SAP** (sleep op de kaart) of
`curl -F file=@"<bestand>" "https://databasehub.sorai.nl/__sap_upload.php?k=<DEPLOY_SECRET>"`
of op de server `php artisan sap:import <pad>`. Herkenning gebeurt op de **kopregel**.
Elke upload is een snapshot (`sap_uploads`); de API leest altijd de laatste. Regels van oude
snapshots worden na `config('sap.bewaar_snapshots')` (30) uploads opgeruimd, de samenvatting blijft.
Alleen contracten die beginnen met `config('sap.depot')` (759) tellen mee.

| Soort | Bestand (SAP-naam) | Frequentie | Tabel |
|---|---|---|---|
| `wow` | `boels_whatsoutwhere_incllimitedmode.xls` (binair .xls, kop rij 2) | dagelijks | `hire_lines` |
| `factuurrun` | `0.07 Contracts and items not part of invoice run.xlsx` (gegroepeerd) | wekelijks | `invoice_run_lines` + `invoice_run_notes` |
| `planning` | `8.7 INDUSTRIAL HOP, SOP and DT View.xlsx` — **platte lijst**, kop `PickUpDate … DelDate` | dagelijks | `planning_orders` + `planning_notes` |
| `planning_regels` | `8.7 … View.xlsx` **gegroepeerde variant** (kop `COLLECTION DATE/TIME`) — optioneel, alleen artikelregels per order | dagelijks | `planning_lines` |
| `reserveringen` | `2.6 Reservations with items (today and future).xlsx` (gegroepeerd) | dagelijks | `reservation_lines` |

## Regels per bestand (besluiten Wim, 12-11-2026)
- **WOW**: kolom `lm` negeren. `EstRtDate`/`Coll. Date` gelijk aan de dominante datum (= rapportdatum)
  betekent "geen einddatum" → NULL. `On-Hire - To Collect` = afgemeld, op te halen (ophaallijst).
  **AVI = Σ Weekly Hire Rate × Qty Out per klant** uit de laatste WOW. `Item Number` ≥ 7 cijfers wordt
  aan `machines.machine_number` gekoppeld (`machine_id`).
- **Factuurrun**: groepregels (`ACCOUNT NAME:`, `CONTRACT NUMBER:`) overslaan; itemregels staan één
  kolom verschoven (klantnummer in C). `DATE LAST INVOICE` 30-12-1899 = nooit gefactureerd (NULL).
  `CREATED BY` = SAP-code van de aanmaker. Per contract een **reden** + status (`invoice_run_notes`),
  blijft staan over uploads; verdwijnt het contract → `verdwenen_op`, komt het terug → weer open.
- **Planning (8.7)**: de platte View is leidend (orders + pickstand); de gegroepeerde variant (kop
  `COLLECTION DATE/TIME`) mag er optioneel naast voor artikelregels (`planning_regels`), kolom A/C negeren. Kolommen `PickUpInfo` en `DelMethod` negeren. `Items` = `gepickt/totaal`.
  `Type` → soort: huur / verkoop / transfer_in / transfer_uit / retour. Leverdatum < vandaag =
  **op te schonen**. `SalesPerson` staat voluit → medewerker via dezelfde codelogica.
- **Reserveringen (2.6)**: kolom **H** `CONTRACT STATUS` bepaalt: `Ingegeven` = contract,
  `Offerte` = offerte; kolom G = offertestatus (Offerte/Winst/Uitstaande); X = orderwaarde;
  Y = aanmakerscode. 8.7 + 2.6 worden samengevoegd tot één rij per contractnummer
  (`PlanningOverzicht`): 8.7 levert soort/pickstand/verkoper, 2.6 levert regels/waarde/periode.

## SAP-code → medewerker (`sap_user_codes`)
Code = eerste 5 letters achternaam zonder tussenvoegsel + 1e letter voornaam (Wouter Zwackhalen =
ZWACKW, Wilco van Bergen = BERGEW, Martijn Kwak = KWAKM). Automatisch gekoppeld als precies één
medewerker past; anders handmatig via Beheer → Uploads SAP → SAP-codes. Namen voluit staan als `NAAM:…`.

## API (auth:sanctum, cookie-relay of token)
- `GET /api/sap/uploads` — laatste stand per soort (+ `verouderd`).
- `GET /api/hire/summary` — weekhuur totaal, per klant (AVI), per project, per status, trend.
- `GET /api/hire/contracts?q=&customer=&project=&status=&sort=&page=&per_page=` · `GET /api/hire/contracts/{nr}`
- `GET /api/hire/to-collect?van=&tm=` — ophaallijst per contract (`te_laat`, `coll_doc_printed`).
- `GET /api/hire/machines/{machinenummer}` — waar staat deze machine (klant/contract/project).
- `GET /api/hire/invoice-run?employee_id=&customer=&status=&zonder_reden=1&nooit=1` ·
  `GET /api/hire/invoice-run/{nr}` · `POST /api/hire/invoice-run/{nr}/note {reden, status}`
- `GET /api/planning/orders?van=&tm=&soort=&employee_id=&q=&verleden=1&alles=1` ·
  `GET /api/planning/orders/{nr}` · `POST /api/planning/orders/{nr}/note {expeditie_employee_id, opmerking, vlag}`
- `GET /api/acties` (eigen open werk: badges + Spoedverhuur/Servicedesk/Planner/Inhuur via DataBrug +
  SAP-signalen) · `GET /api/acties/alle` (super-admin of Dashboard-rol area-manager/bu-manager/admin).
